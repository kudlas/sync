<?php


class MailDto implements IMail
{
    /** @var PhpImap\IncomingMail $mailData */
    public $mailData;
    private $data = [];

    public function __construct($mailData)
    {
        $this->mailData = $mailData;
    }

    public function get($field)
    {
        return $this->data[$field];
    }

    public function set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function setAssoc($data)
    {
        $this->data = array_merge($this->data, $data);
    }

    public function fieldExists($field): bool
    {
        return array_key_exists($field, $this->data);
    }

    public function getFields($keys): array
    {
        return array_intersect_key($this->data, array_flip($keys));
    }

    /**
     * @return \PhpImap\IncomingMail
     */
    public function getMailData(): \PhpImap\IncomingMail
    {
        return $this->mailData;
    }

}