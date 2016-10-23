<?php
namespace Media\MediaBundle\Services;

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * service for set the md5 password of a registered user
 * password is pure md5 no any merging of salt.
 */
class CustomMd5PasswordEncoderService implements PasswordEncoderInterface
{
    public function __construct() {
    }

    public function encodePassword($raw, $salt) {
        return md5($raw);
    }

    public function isPasswordValid($encoded, $raw, $salt) {
        return md5($raw) == $encoded;
    }
}