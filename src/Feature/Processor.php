<?php

namespace Prisjakt\Unleash\Feature;

use Prisjakt\Unleash\Strategy\Repository;

class Processor
{
    private $repository;

    public function __construct(Repository $strategyRepository)
    {
        $this->repository = $strategyRepository;
    }

    public function process(Feature $feature, array $context, bool $default): bool
    {
        if (!$feature->isEnabled()) {
            return false;
        }
        /** @var Strategy $featureStrategy */
        foreach ($feature->getStrategies() as $featureStrategy) {
            if (!$this->repository->has($featureStrategy->getName())) {
                return false; // TODO: should we just return? log? throw?
            }

            $strategy = $this->repository->get($featureStrategy->getName());
            if ($strategy->isEnabled($featureStrategy->getParameters(), $context)) {
                return true;
            }
        }
        return $default;
    }
}
