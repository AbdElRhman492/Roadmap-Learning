<?php

declare(strict_types=1);

namespace PAPP\Contracts;

use PAPP\Entities\Submission;
use PAPP\Entities\Result;

interface Gradable
{
  public function grade(Submission $submission): Result;
}
