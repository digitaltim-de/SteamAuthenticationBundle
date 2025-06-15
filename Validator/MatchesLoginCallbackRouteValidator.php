<?php

namespace Knojector\SteamAuthenticationBundle\Validator;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * @author knojector <dev@knojector.xyz>
 */
class MatchesLoginCallbackRouteValidator extends ConstraintValidator
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {}

    /**
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $expected = $this->urlGenerator->generate('steam_authentication_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        if ($expected !== $value) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ url }}', $value)
                ->setParameter('{{ expected }}', $expected)
                ->addViolation();
        }
    }
}
