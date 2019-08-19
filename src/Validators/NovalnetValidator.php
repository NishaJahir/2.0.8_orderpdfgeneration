<?php

namespace Novalnet\Validators;

use Plenty\Validation\Validator;

/**
 *  Validator Class
 */
class NovalnetValidator extends Validator
{
    protected function defineAttributes()
    {
        $this->addString('transactionDetails', true);
    }
}
