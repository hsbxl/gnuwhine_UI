<?php

namespace Drupal\gnuwhine_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Gitonomy\Git\Repository;
use Symfony\Component\Yaml\Yaml;

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
    $client->authenticate('6db80d761d77ed184fa1451f8018566a5ed84515', NULL, 'http_token');
    $recipe_branches = [];

    $username = 'hsbxl';
    $repo = 'gnuwhine';

    $branches = $client->api('repo')->branches($username, $repo);
    foreach ($branches as $branch) {
      $recipe_branches['hsbxl/gnuwhine']['branches'][] = $branch['name'];
    }

    $forks = $client->api('repo')->forks()->all($username, $repo);
    foreach ($forks as $fork) {
      $username = $fork['owner']['login'];
      $repo = $fork['name'];

      $branches = $client->api('repo')->branches($username, $repo);
      foreach ($branches as $branch) {
        $recipe_branches[$fork['full_name']]['branches'][] = $branch['name'];
      }
    }

    foreach ($recipe_branches as $key => $recipe) {
      if(!file_exists('/tmp/' . $key)) {
        $url = 'https://github.com/' . $key . '.git';
        \Gitonomy\Git\Admin::cloneTo('/tmp/' . $key, $url, FALSE);
      }

      foreach ($recipe['branches'] as $branch) {
        $repository = new \Gitonomy\Git\Repository('/tmp/' . $key);
        $repository->run('fetch');
        $repository->run('checkout', [$branch]);

        $recipe = Yaml::parse(file_get_contents('/tmp/' . $key . '/recipe.yaml'));

        if(!empty($filtered_recipes)) {
          $double = FALSE;
          foreach ($filtered_recipes as $filtered_recipe) {
            if($recipe === $filtered_recipe) {
              $double = TRUE;
            }
          }
          if(!$double) {$filtered_recipes[$key . '::' . $branch] = $recipe;}
        }
        else {
          $filtered_recipes[$key . '::' . $branch] = $recipe;
        }
      }
    }

    ksm($filtered_recipes);

    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: fork')
    ];
  }

}