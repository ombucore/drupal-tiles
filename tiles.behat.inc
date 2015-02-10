<?php


use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\DrupalDriverManager;

class TilesContext extends RawMinkContext implements DrupalSubContextInterface {
    private $drupal;

    public function __construct(DrupalDriverManager $drupal) {
      $this->drupal = $drupal;
    }

    public static function getAlias() {
        return 'tiles';
    }

    /**
     * @Then /^There is a tile with title "([^"]*)"$/
     */
    public function thereIsATileWithTitle($heading) {
        $element = $this->getSession()->getPage();
        foreach (array('h2', 'h3') as $tag) {
            $results = $element->findAll('css', '.bean-rte-rte ' . $tag);
            foreach ($results as $result) {
                if ($result->getText() == $heading) {
                    return;
                }
            }
        }
        throw new \Exception(sprintf("The text '%s' was not found in any Tile'
         . 'heading on the page %s", $heading, $this->getSession()
            ->getCurrentUrl()));
    }

    /**
     * @Then /^There are (\d+) (\w+) titles$/
     */
    public function thereAreRteTitles($count, $type) {
        $element = $this->getSession()->getPage();
        $nodes = $element->findAll('css', '.bean-rte-rte');
        $found = count($nodes);
        if($found != $count) {
            throw new Exception("Found ${found}." . "  Expected $count $type
            tiles and");
        }

    }

}
