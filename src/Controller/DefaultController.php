<?php

namespace Drupal\gnuwhine_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\gnuwhine_ui\GnuwhineService;
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
   *   Return menu.
   */
  public function fork() {
    $recipes = $this->gnuwhineservice->getRecipes();
    $form = \Drupal::formBuilder()->getForm('Drupal\gnuwhine_ui\Form\RecipeForm');
    return $form;
  }
}