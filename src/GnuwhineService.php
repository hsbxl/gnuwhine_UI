<?php

namespace Drupal\gnuwhine_ui;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Datetime\DrupalDateTime;

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

    $branches = $client->api('repo')->branches($username, $repo);
    foreach ($branches as $branch) {
      $recipe_branches[$path]['branches'][] = $branch['name'];
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
        $recipes[$key . '::' . $branch]['ingredients'] = $recipe;
      }
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
        $unitprice = $ingredients[$ingredient];
        $amount_ml = ($glass_size / 100) * (int)$amount_percentage;
        $recipes[$key]['price'] = round($recipes[$key]['price'] + ($unitprice * $amount_ml), 1);
        $a = 0;
      }
    }

    return $recipes;
  }

}