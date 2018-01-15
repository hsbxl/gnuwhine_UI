<?php

namespace Drupal\gnuwhine_ui;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Cache\Cache;
use Symfony\Component\Yaml\Yaml;

/**
 * Class GnuwhineService.
 */
class GnuwhineService {

  public function __construct(QueryFactory $entity_query, EntityManagerInterface $entityManager) {
    $this->entity_query = $entity_query;
    $this->entityManager = $entityManager;
    $this->config = \Drupal::config('gnuwhine_ui.settings');
    $this->ingredients = Yaml::parse($this->config->get('ingredients'));
    $this->glasssize = $this->config->get('glass_size');
    $this->github_token = $this->config->get('github_token') ? : '';
    $this->github_genesis_recipe = $this->config->get('github_recipe');
  }

  public function pour($recipe) {

    // Calculate the timing of the pumps.
    $this->calculate_timing($recipe);

    // Update stats.
    $this->updateStats($recipe);
  }

  public function updateStats($recipe) {
    $pours = $this->config->get($recipe['name']) + 1;
    \Drupal::configFactory()->getEditable('gnuwhine_ui.settings')
      ->set($recipe['name'], $pours)
      ->save();
  }

  public function calculate_timing(&$recipe = []) {
    $stock = $this->getIngredients();
    foreach ($recipe['ingredients'] as $key => $percentage) {
      $ml = $this->calculate_ml((int)$percentage);
      $pretiming = !empty($stock[$key]['pretime']) ? $stock[$key]['pretime'] : 0;
      $timing = ($ml * $stock[$key]['timing']) + $pretiming;
      $recipe['ingredients'][$key] = $timing;
      $recipe['pumps'][$stock[$key]['pump']] = $timing;
    }

    return $recipe;
  }

  public function calculate_ml($percentage) {
    return ((int)$percentage / 100) * $this->glasssize;
  }

  public function getIngredients() {
    return $this->ingredients;
  }

  public function getRecipes() {
    $recipe_branches = [];

    $client = new \Github\Client();
    $client->authenticate($this->github_token, NULL, 'http_token');

    $genesis_link = parse_url($this->github_genesis_recipe);
    $path = ltrim($genesis_link['path'], '/');
    $genesis_path = explode('/', $path);
    $username = $genesis_path[0];
    $repo = $genesis_path[1];


    $branches_cid = 'gnuwhine:genesis:branches';
    if($cache = \Drupal::cache('data')->get($branches_cid)) {
      $branches = $cache->data;
    }
    else {
      $branches = $client->api('repo')->branches($username, $repo);
      \Drupal::cache('data')->set($branches_cid, $branches, Cache::PERMANENT, array(
        'gnuwhine:branches',
      ));
    }

    foreach ($branches as $branch) {
      $recipe_branches[$path]['branches'][] = $branch['name'];
    }


    $forks_cid = 'gnuwhine:forks';
    if($cache = \Drupal::cache('data')->get($forks_cid)) {
      $forks = $cache->data;
    }
    else {
      $forks = $client->api('repo')->forks()->all($username, $repo);
      \Drupal::cache('data')->set($forks_cid, $forks, Cache::PERMANENT, array(
        'gnuwhine:forks',
      ));
    }


    $recipe_branches_cid = 'gnuwhine:recipe_branches';
    if($cache = \Drupal::cache('data')->get($recipe_branches_cid)) {
      $recipe_branches = $cache->data;
    }
    else {
      foreach ($forks as $fork) {
        $username = $fork['owner']['login'];
        $repo = $fork['name'];

        $branches = $client->api('repo')->branches($username, $repo);
        foreach ($branches as $branch) {
          $recipe_branches[$fork['full_name']]['branches'][] = $branch['name'];
        }
      }

      foreach ($forks as $fork) {
        $username = $fork['owner']['login'];
        $repo = $fork['name'];

        $branches = $client->api('repo')->branches($username, $repo);
        foreach ($branches as $branch) {
          $recipe_branches[$fork['full_name']]['branches'][] = $branch['name'];
        }
      }

      \Drupal::cache('data')->set($recipe_branches_cid, $recipe_branches, Cache::PERMANENT, array(
        'gnuwhine:recipe_branches',
      ));
    }


    $branches_cid = 'gnuwhine:recipes';
    if($cache = \Drupal::cache('data')->get($branches_cid)) {
      $recipes = $cache->data;
    }
    else {

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
          $recipes[$key . '::' . $branch]['name'] = $key . '::' . $branch;
          $recipes[$key . '::' . $branch]['ingredients'] = $recipe;
        }
      }

      \Drupal::cache('data')->set($branches_cid, $recipes, Cache::PERMANENT, array(
        'gnuwhine:recipes',
      ));
    }

    $filtered_recipes = $this->filterRecipesDoubles($recipes);
    $filtered_recipes = $this->filterRecipesIngredients($filtered_recipes);
    $filtered_recipes = $this->calculate_prices($filtered_recipes);

    return $filtered_recipes;
  }

  public function filterRecipesDoubles($recipes) {
    foreach($recipes as $key => $recipe) {
      if(!empty($filtered_recipes)) {
        $double = FALSE;

        foreach ($filtered_recipes as $filtered_recipe) {
          if($recipe['ingredients'] === $filtered_recipe['ingredients']) {
            $double = TRUE;
          }
        }
        if(!$double) {
          $filtered_recipes[$key] = $recipe;
        }
      }
      else {
        $filtered_recipes[$key] = $recipe;
      }
    }
    return $filtered_recipes;
  }

  public function filterRecipesIngredients($recipes) {
    $ingredients = array_keys($this->ingredients);

    foreach ($recipes as $key => $recipe) {
      if (!array_diff(array_keys($recipe['ingredients']), $ingredients)) {
        $output[$key] = $recipe;
      }
    }

    return $output;
  }

  public function calculate_prices($recipes) {
    $ingredients = $this->ingredients;
    $glass_size = $this->glasssize;

    foreach ($recipes as $key => $recipe) {
      foreach ($recipe['ingredients'] as $ingredient => $amount_percentage) {
        $unitprice = $ingredients[$ingredient]['price'];
        $amount_ml = ($glass_size / 100) * (int)$amount_percentage;
        $recipes[$key]['price'] = round($recipes[$key]['price'] + ($unitprice * $amount_ml), 1);
      }
    }

    return $recipes;
  }

  public function filtername($name) {
    $name = str_replace('::vanilla', '', $name);
    $name = str_replace('/gnuwhine', '', $name);
    $name = str_replace('::', ' ', $name);
    $name = str_replace('hsbxl', ' ', $name);
    $name = trim($name);

    if(empty($name)) {
      return 'Gnüwhine Vanilla';
    }

    return ucwords($name);
  }
}