<?php

/*
 * This file is part of composer/semver.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace BracketSpace\Notification\Dependencies\Composer\Semver\Constraint;

/**
 * Blackhole of constraints, nothing escapes it
 */
class MatchNoneConstraint implements ConstraintInterface
{
    /** @var string|null */
    protected $prettyString;

    /**
     * @param ConstraintInterface $provider
     *
     * @return bool
     */
    public function matches(ConstraintInterface $provider)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function compile($otherOperator)
    {
        return 'false';
    }

    /**
     * {@inheritDoc}
     */
    public function setPrettyString($prettyString)
    {
        $this->prettyString = $prettyString;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrettyString()
    {
        if ($this->prettyString) {
            return $this->prettyString;
        }

        return (string) $this;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return '[]';
    }

    /**
     * {@inheritDoc}
     */
    public function getUpperBound()
    {
        return new Bound('0.0.0.0-dev', false);
    }

    /**
     * {@inheritDoc}
     */
    public function getLowerBound()
    {
        return new Bound('0.0.0.0-dev', false);
    }
}
