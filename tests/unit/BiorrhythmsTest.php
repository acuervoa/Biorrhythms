<?php

require_once __DIR__ . "/../../vendor/autoload.php";

use \Biorrhythms\Biorrhythms;

class BiorrhythmsTest extends \Codeception\TestCase\Test
{

    use Codeception\Specify;

    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester = new Biorrhythms();
    }

    protected function _after()
    {
    }

    // tests

    private function provider(){
        return array(
            "calculatePhysical" => array([0, 0], [1, 0.27318093483271549]),
            "calculateEmotional" => array([0, 0], [1, 0.22439890157779652]),
            "calculateIntellectual" => array([0, 0], [1, 0.19039920433321803])
        );
    }

    public function testCalculatePhysical()
    {
        $this->specify("calculatePhysical", function($time, $result){
            verify($this->tester->calculatePhysical($time))->equals($result);
        },["examples" => $this->provider()['calculatePhysical']]);
    }

    public function testCalculateEmotional()
    {
        $this->specify("calculateEmotional", function($time, $result){
            verify($this->tester->calculateEmotional($time))->equals($result);
        },["examples" => $this->provider()['calculateEmotional']]);
    }

    public function testCalculateIntellectual()
    {

        $this->specify("calculateIntellectual", function($time, $result){
            verify($this->tester->calculateIntellectual($time))->equals($result);
        },["examples" => $this->provider()['calculateIntellectual']]);
    }
}
