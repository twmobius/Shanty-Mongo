<?php

namespace Shanty\Mongo\Validator;

use Zend\Validator\AbstractValidator;

class ArrayValidator extends AbstractValidator
{
	const NOT_ARRAY = 'notArray';

	protected $_messageTemplates = array(
		self::NOT_ARRAY => "Value is not an Array"
	);

	public function isValid($value)
	{
		if (!is_array($value)) {
			$this->_error(self::NOT_ARRAY);
			return false;
		}

		return true;
	}
}
