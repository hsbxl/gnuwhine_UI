<?php

namespace Drupal\gnuwhine_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\gnuwhine_ui\GnuwhineService;
use Gitonomy\Git\Repository;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerBase {

  public static function create(ContainerInterface $container) {
    $GnuwhineService = $container->get('gnuwhine_ui.gnuwhine');
    return new static($GnuwhineService);
  }

  public function __construct(GnuwhineService $gnuwhineService) {
    $this->gnuwhineservice = $gnuwhineService;
  }

  /**
   * Fork.
   *
   * @return string
   *   Return Hello string.
   */
  public function fork() {

    $recipes = $this->gnuwhineservice->getRecipes();
    ksm($recipes);

    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: fork')
    ];
  }

  public function check_ingredients($recipes) {
    $config = \Drupal::config('gnuwhine_ui.settings');
    $ingredients = array_keys(Yaml::parse($config->get('ingredients')));

    foreach ($recipes as $key => $recipe) {
      if (!array_diff(array_keys($recipe['ingredients']), $ingredients)) {
        $output[$key] = $recipe;
      }
    }

    return $output;
  }

  public function check_doubles($recipes) {
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

  public function calculate_prices($recipes) {
    $config = \Drupal::config('gnuwhine_ui.settings');
    $ingredients = Yaml::parse($config->get('ingredients'));
    $glass_size = $config->get('glass_size');

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