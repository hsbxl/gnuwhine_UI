<?php

namespace Drupal\gnuwhine_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use GitWrapper\GitWrapper;

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
    $client = new \Github\Client();
    $client->authenticate('fd63540c7408f6be9a9533beee9f808586ae1202', NULL, 'http_token');
    $recipes = [];

    $username = '0x20';
    $repo = 'HTH';

    $branches = $client->api('repo')->branches($username, $repo);
    foreach ($branches as $branch) {
      $recipes['0x20/HTH']['branches'][] = $branch['name'];
    }

    $forks = $client->api('repo')->forks()->all($username, $repo);
    foreach ($forks as $fork) {
      $username = $fork['owner']['login'];
      $repo = $fork['name'];

      $branches = $client->api('repo')->branches($username, $repo);
      foreach ($branches as $branch) {
        $recipes[$fork['full_name']]['branches'][] = $branch['name'];
      }
    }

    $gitWrapper = new GitWrapper();

    ksm($recipes);

    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: fork')
    ];
  }

}