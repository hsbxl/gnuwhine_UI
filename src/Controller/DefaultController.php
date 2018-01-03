<?php

namespace Drupal\gnuwhine\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerBase {

  /**
   * Fork.
   *
   * @return string
   *   Return Hello string.
   */
  public function fork() {

    //$client = new \Github\Client();
    //$repositories = $client->api('user')->repositories('ornicar');


    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: fork')
    ];
  }

}
