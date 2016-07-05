<?php

namespace OCA\Owncollab_Talks;

use OC\User\Session;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCA\Owncollab_Talks\PHPMailer\PHPMailer;

class TalkMail
{

    const SEND_STATUS_CREATED = 0;
    const SEND_STATUS_OPEN = 1;
    const SEND_STATUS_CLOSE = 2;
    const SEND_STATUS_WAIT = 3;



    static public function createHash($salt) {
        return md5(date("Y-m-d h:i:s").$salt);
    }

    static public function createAddress($uid) {
        return $uid.'@'.\OC::$server->getRequest()->getServerHost();
    }

    static public function groupsEmailSend() {

        return true;
    }

    /**
     * @param array $from
     * @param array $reply
     * @param array $to
     * @param $subject
     * @param $body
     * @return bool|string
     * @throws PHPMailer\phpmailerException
     */
    static public function createMail(array $from, array $reply, array $to, $subject, $body)
    {
        $mail = new PHPMailer();
        $mail->CharSet = "UTF-8";
        $mail->setFrom($from[0], $from[1]);
        $mail->addReplyTo($reply[0], $reply[1]);

        foreach($to as $_to) {

            $mail->addAddress($_to[0], $_to[1]);
        }

        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->isHTML();

        /*return [$from, $reply, $to, $subject, $body];*/

        if (!$mail->send())
            return $mail->ErrorInfo;
        else
            return true;
    }






}