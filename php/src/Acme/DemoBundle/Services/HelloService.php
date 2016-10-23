<?php
namespace Acme\DemoBundle;

class HelloService
{
    private $mailer;

    public function __construct(\Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function hello($name)
    {

        $message = \Swift_Message::newInstance()
                                ->setTo('me@example.com')
                                ->setSubject('Hello Service')
                                ->setBody($name . ' says hi!');

        $this->mailer->send($message);


        return 'Hello, ' . $name;
    }
}