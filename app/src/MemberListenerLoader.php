<?php

use Amp\Future;
use ArchiPro\EventDispatcher\ListenerProvider;
use ArchiPro\Silverstripe\EventDispatcher\Contract\ListenerLoaderInterface;
use ArchiPro\Silverstripe\EventDispatcher\Event\DataObjectEvent;
use ArchiPro\Silverstripe\EventDispatcher\Event\Operation;
use ArchiPro\Silverstripe\EventDispatcher\Listener\DataObjectEventListener;
use SilverStripe\Control\Email\Email;
use SilverStripe\Security\Member;
use SilverStripe\Control\Director;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * This class will set a temporary password for new members and email it to them.
 */
class MemberListenerLoader implements ListenerLoaderInterface
{
    public function loadListeners(ListenerProvider $provider): void
    {
        DataObjectEventListener::create(
            Closure::fromCallable([$this, 'onMemberCreated']),
            [Member::class],
            [Operation::CREATE]
        )->selfRegister($provider);
    }

    /**
     * @param DataObjectEvent<Member> $event
     * @return void
     */
    public function onMemberCreated(DataObjectEvent $event): void
    {
        sleep(10);

        /** @var Member $member */
        $member = $event->getObject();

        // Set a new temporary password
        $randomPassword = $member->generateRandomPassword(15);
        $member->changePassword($randomPassword);
        $member->PasswordExpiry = DBDatetime::now()->Rfc2822();
        $member->write();

        $siteTitle = SiteConfig::current_site_config()->Title;
        $loginURL = Director::absoluteURL(Security::login_url() . '?BackURL=%2Fadmin%2Fpages');
        $loginURL = str_replace('http://', 'https://', $loginURL);

        $message = <<<HTML
        <p>Welcome to our site.</p>
        <p>Your temporary password is: <strong>$randomPassword</strong></p>
        <p>You can change your password after logging in.</p>
        <p><a href="$loginURL">Login</a></p>
        HTML;

        $email = $member->Email;

        Email::create()
            ->setTo($email)
            ->setSubject("Welcome to $siteTitle")
            ->setFrom('no-reply@example.com')
            ->setBody($message)
            ->send();
    }
}
