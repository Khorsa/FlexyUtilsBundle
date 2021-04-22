<?php

namespace flexycms\FlexyUtilsBundle\Utils;

use PHPMailer\PHPMailer\PHPMailer;

class MailProcessor
{
    private $from = null;
    private $replayTo = null;
    private $to = array();
    private $cc = array();
    private $bcc = array();
    private $subject = '';
    private $text = '';

    private $files = array();

    public function __construct()
    {
    }

    public function send()
    {
        try {
            $mail = new PHPMailer();
            $mail->IsHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Hostname = $_SERVER["HTTP_HOST"];

            if ($this->replayTo == null || $this->replayTo[0] == null) {
                $this->replayTo = array($this->from[0], $this->from[1]);
            }

            if ($this->from == null || $this->from[0] == null) {
                $host = $_SERVER["HTTP_HOST"];
                $this->from = array('postmaster@' . $host, isset($replayTo->from[1]) ? $replayTo->from[1] : '');
            }

            $mail->SetFrom($this->from[0], $this->from[1]);

            if ($this->replayTo[0] !== null) $mail->AddReplyTo($this->replayTo[0], $this->replayTo[1]);

            foreach ($this->to as $item) $mail->AddAddress($item[0], $item[1]);
            foreach ($this->cc as $item) $mail->AddCC($item[0], $item[1]);
            foreach ($this->bcc as $item) $mail->AddBCC($item[0], $item[1]);

            $mail->Subject = $this->subject;
            $mail->Body = $this->text;
            $mail->AltBody = strip_tags(str_replace('<br>', "\r\n", $this->text));

            foreach ($this->files as $file) {
                $mail->AddAttachment($file[0], $file[1]);
            }

            $mail->Send();
        }
        catch (\Exception $ex) {
            return $ex->getMessage();
        }
        return $this;
    }



    public function from($email, $name = null)
    {
        if ($name == null) $name = $email;
        $this->from = array($email, $name);
        return $this;
    }
    public function replayTo($email, $name = null)
    {
        if ($name == null) $name = $email;
        $this->replayTo = array($email, $name);
        return $this;
    }
    public function to($email, $name = null)
    {
        if ($name == null) $name = $email;
        $this->to[] = array($email, $name);
        return $this;
    }
    public function cc($email, $name = null)
    {
        if ($name == null) $name = $email;
        $this->cc[] = array($email, $name);
        return $this;
    }
    public function bcc($email, $name = null)
    {
        if ($name == null) $name = $email;
        $this->bcc[] = array($email, $name);
        return $this;
    }
    public function subject($subject = '')
    {
        $this->subject = $subject;
        return $this;
    }
    public function text($text = '')
    {
        $this->text = $text;
        return $this;
    }

    public function addFile($file, $name)
    {
        if (!is_file($file)) return $this;
        $this->files[] = array($file, $name);
        return $this;
    }


}