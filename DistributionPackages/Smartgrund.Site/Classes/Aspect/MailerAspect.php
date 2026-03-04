<?php

declare(strict_types=1);

namespace Smartgrund\Site\Aspect;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\SymfonyMailer\Exception\InvalidMailerConfigurationException;
use Symfony\Component\Mailer\Bridge\MicrosoftGraph\Transport\MicrosoftGraphTransportFactory;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;

#[Flow\Aspect]
class MailerAspect
{
    #[Flow\InjectConfiguration(path: "mailer", package: "Neos.SymfonyMailer")]
    protected array $mailerConfiguration;

    #[Flow\Around("method(Neos\SymfonyMailer\Service\MailerService->getMailer())")]
    public function addMicrosoftGraphTransport(JoinPointInterface $joinPoint): Mailer
    {
        $transport = $joinPoint->getMethodArgument('transport');
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
