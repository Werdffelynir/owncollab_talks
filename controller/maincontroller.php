<?php
/**
 * ownCloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Your Name <mail@example.com>
 * @copyright Your Name 2016
 */

namespace OCA\Owncollab_Talks\Controller;

use OC\Files\Filesystem;
use OCA\Owncollab_Talks\AppInfo\Aliaser;
use OCA\Owncollab_Talks\AppInfo\TempFile;
use OCA\Owncollab_Talks\Db\Connect;
use OCA\Owncollab_Talks\Helper;
use OCA\Owncollab_Talks\MailParser;
use OCA\Owncollab_Talks\ParseMail;
use OCP\Files;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\Share;

class MainController extends Controller
{

    /** @var string current auth user id */
    private $userId;
    private $l10n;
    private $isAdmin;
    private $connect;
    private $projectname = "Base project";
    private $mailDomain = null;

    /**
     * MainController constructor.
     * @param string $appName
     * @param IRequest $request
     * @param $userId
     * @param $isAdmin
     * @param $l10n
     * @param Connect $connect
     */
    public function __construct(
        $appName,
        IRequest $request,
        $userId,
        $isAdmin,
        $l10n,
        Connect $connect
    )
    {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->isAdmin = $isAdmin;
        $this->l10n = $l10n;
        $this->connect = $connect;
        $this->mailDomain = Aliaser::getMailDomain();

    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index()
    {
        if (!$this->mailDomain) {
            $error = "<p>Failed to get a domain name</p>";
            if (!Aliaser::getMTAConnection())
                $error .= "<p>Do not connect to an MTA database</p>";
            return $this->pageError($error);
        } else
            return $this->begin();
    }

    /**
     * Blocked application and show error message
     * @param $error_message
     * @return TemplateResponse
     */
    public function pageError($error_message)
    {

        $data = [
            'menu' => '',
            'content' => 'error',
            'user_id' => $this->userId,
            'error_message' => $error_message,
        ];

        return new TemplateResponse($this->appName, 'main', $data);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function begin()
    {

        $data = [
            'menu' => 'begin',
            'content' => 'begin',
            'user_id' => $this->userId,
            'groupsusers' => $this->connect->users()->getGroupsUsersList(),
            'nogroup' => $this->connect->users()->getUngroupUsers(),
        ];

        return new TemplateResponse($this->appName, 'main', $data);
    }


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function started()
    {

        $data = [
            'menu' => 'started',
            'content' => 'list',
            'messages' => $this->connect->messages()->getStarted($this->userId),
        ];

        return new TemplateResponse($this->appName, 'main', $data);
    }


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function my()
    {
        $messages = $this->connect->messages()->getMy($this->userId);
        $data = [
            'menu' => 'my',
            'content' => 'list',
            'messages' => $messages,
        ];
        return new TemplateResponse($this->appName, 'main', $data);
    }


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function all()
    {

        $data = [
            'menu' => 'all',
            'content' => 'list',
            'messages' => $this->connect->messages()->getAll(),
        ];

        return new TemplateResponse($this->appName, 'main', $data);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function read($id)
    {
        $message = $this->connect->messages()->getById((int)$id);
        $parent = $this->connect->messages()->getById((int)$message[0]['rid']);
        $attachements_info = [];

        if(!empty($message[0]['attachements'])) {
            try{
                $attach = json_decode($message[0]['attachements'], true);
                foreach($attach as $at){
                    $file = $this->connect->files()->getById($at);
                    if($file) {
                        $path = str_replace('files/', '',  $file['path']);
                        $fileInfo = \OC\Files\Filesystem::getFileInfo($path);
                        $attachements_info[] = [
                            'preview' => \OC_Helper::previewIcon( $path ),
                            'link' => "/remote.php/webdav/$path",
                            'file' => $file,
                            'info' => \OCA\Files\Helper::formatFileInfo($fileInfo),
                        ];
                    }
                }
            }catch(\Exception $e){
                var_dump('Exception: '.$e->getMessage());
            }
        }

        Helper::cookies('goto_message', ($message[0]['rid'] == 0 ? $message[0]['id'] : $parent[0]['id']), 0, '/');

        $data = [
            'menu' => 'all',
            'content' => 'read',
            'message' => $message,
            'parent' => $parent,
            'attachements_info' => $attachements_info,
        ];

        return new TemplateResponse($this->appName, 'main', $data);
    }


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function test()
    {
        $data = [];
        //$data['files']

        $files = \OCA\Files\Helper::getFiles('/Talks');
        $data['files'] = $files;

        $data['isShared'] = $files[0]->isShared();

        //$list = \OCA\Files\Helper::formatFileInfo($files[0]);
        //$data['list'] = $list;


        var_dump($data);
        return new DataResponse($data);
    }


}