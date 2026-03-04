<?php

declare(strict_types=1);

namespace Smartgrund\Site\Service;

use Neos\Flow\Annotations as Flow;
use Neos\SymfonyMailer\Exception\InvalidMailerConfigurationException;
use Neos\SymfonyMailer\Service\MailerService;
use Symfony\Component\Mailer\Bridge\MicrosoftGraph\Transport\MicrosoftGraphTransportFactory;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;

#[Flow\Scope("singleton")]
class MicrosoftGraphMailerService extends MailerService
{
    public function getMailer(?TransportInterface $transport = null): Mailer
    {
        if ($transport !== null) {
            return new Mailer($transport);
        }

        if (!isset($this->mailerConfiguration['dsn'])) {
            throw new InvalidMailerConfigurationException(
                'No DSN configured for Neos.SymfonyMailer',
                1739540476
            );
        }

        $factories = iterator_to_array(Transport::getDefaultFactories());
        array_unshift($factories, new MicrosoftGraphTransportFactory());
        $transport = (new Transport($factories))->fromString($this->mailerConfiguration['dsn']);

        return new Mailer($transport);
    }
}
