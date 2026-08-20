<?php

declare(strict_types=1);

namespace VTInnovations\SimpleNotifyBundle\Token;

class TokenRegistry
{
    /**
     * @param iterable<TokenProviderInterface> $providers
     */
    public function __construct(private readonly iterable $providers)
    {
    }

    /**
     * Every token available to a notification of this type: the universal ones plus the
     * ones its trigger supplies.
     *
     * @return array<string, string> Token name => description, sorted by name
     */
    public function getDefinitionsFor(string $type): array
    {
        $definitions = [];

        foreach ($this->providers as $provider) {
            if (TokenProviderInterface::TYPE_ANY !== $provider->getType() && $provider->getType() !== $type) {
                continue;
            }

            $definitions = [...$definitions, ...$provider->getDefinitions()];
        }

        ksort($definitions);

        return $definitions;
    }

    /**
     * Token values contributed automatically, merged under the caller's own tokens: a form
     * field called "host" must win over the universal ##host##, because that is the one the
     * editor was looking at when writing the message.
     *
     * @param array<string, string> $tokens
     *
     * @return array<string, string>
     */
    public function withProvidedValues(array $tokens, string $type): array
    {
        $provided = [];

        foreach ($this->providers as $provider) {
            if (TokenProviderInterface::TYPE_ANY !== $provider->getType() && $provider->getType() !== $type) {
                continue;
            }

            $provided = [...$provided, ...$provider->getValues()];
        }

        return [...$provided, ...$tokens];
    }
}
