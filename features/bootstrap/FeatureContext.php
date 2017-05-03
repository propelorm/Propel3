<?php
/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 *
 */

use Behat\Gherkin\Node\PyStringNode;
use Behat\Behat\Tester\Exception\PendingException;

/**
 * Class FeatureContext
 *
 * @author William Durand
 * @author Cristiano Cinotti
 */
class FeatureContext extends PropelContext
{
    /**
     * @Then /^It should contain:$/
     */
    public function itShouldContain(PyStringNode $string)
    {
        $sql = file_get_contents($this->workingDirectory . '/default.sql');

        if (empty($sql)) {
            throw new Exception('Content not found');
        }

        \PHPUnit_Framework_Assert::assertContains($this->getSql($string->getRaw()), $sql);
    }

    /**
     * @Then I should see:
     */
    public function iShouldSee(PyStringNode $string)
    {
        throw new PendingException();
    }
}
