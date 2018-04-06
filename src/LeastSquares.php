<?php

namespace Rubix\Engine;

use MathPHP\LinearAlgebra\Vector;
use MathPHP\LinearAlgebra\MatrixFactory;
use InvalidArgumentException;

class LeastSquares implements Regression
{
    const CATEGORICAL = 1;
    const CONTINUOUS = 2;

    /**
     * The computed y intercept.
     *
     * @var float
     */
    protected $intercept;

    /**
     * The computed coefficients of the training data.
     *
     * @var array
     */
    protected $coefficients = [
        //
    ];

    /**
     * @return float|null
     */
    public function intercept() : ?float
    {
        return $this->intercept;
    }

    /**
     * @return array
     */
    public function coefficients() : array
    {
        return $this->coefficients;
    }

    /**
     * Learn the coefficients of the training data.
     *
     * @param  \Rubix\Engine\SupervisedDataset  $data
     * @throws \InvalidArgumentException
     * @return void
     */
    public function train(SupervisedDataset $data) : void
    {
        foreach ($data->types() as $type) {
            if ($type !== self::CONTINUOUS) {
                throw new InvalidArgumentException('This estimator only works with continuous input data.');
            }
        }

        $coefficients = $this->computeCoefficients($data->samples(), $data->outcomes());

        $this->intercept = array_shift($coefficients);
        $this->coefficients = $coefficients;
    }

    /**
     * Make a prediction of a given sample.
     *
     * @param  array  $sample
     * @return array
     */
    public function predict(array $sample) : array
    {
        $outcome = $this->intercept;

        foreach ($this->coefficients as $i => $coefficient) {
            $outcome += $coefficient * $sample[$i];
        }

        return [
            'outcome' => $outcome,
        ];
    }

    /**
     * Compute the coefficients of the training data by solving for the normal equation.
     *
     * @param  array  $samples
     * @param  array  $outcomes
     * @return array
     */
    protected function computeCoefficients(array $samples, array $outcomes) : array
    {
        foreach ($samples as &$sample) {
            array_unshift($sample, 1);
        }

        $samples = MatrixFactory::create($samples);
        $outcomes = MatrixFactory::create([new Vector($outcomes)]);

        return $samples->transpose()->multiply($samples)->inverse()
            ->multiply($samples->transpose()->multiply($outcomes))
            ->getColumn(0);
    }
}