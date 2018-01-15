<?php

namespace Drupal\gnuwhine_ui\form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\gnuwhine_ui\GnuwhineService;

/**
 * Configure example settings for this site.
 */
class AdminForm extends ConfigFormBase {

  public function __construct(GnuwhineService $gnuwhineService) {
    $this->gnuwhine = $gnuwhineService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('gnuwhine_ui.gnuwhine')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gnuwhine_ui_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'gnuwhine_ui.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('gnuwhine_ui.settings');

    $form['ingredients'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Ingredients'),
    );

    $form['ingredients']['ingredients'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Ingredients, with price'),
      '#default_value' => $config->get('ingredients') ? : '',
      '#rows' => 10,
      '#description' => $this->t('List all available ingredients. One per line. Add the price per ml. Ex. rum: 0.1')
    );

    $form['ingredients']['filter_recipes'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Filter recipes'),
      '#default_value' => $config->get('filter_recipes') ? : '',
      '#description' => $this->t('Show only recipes where we have all ingredients available.')
    );

    $form['recipes'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Recipes'),
    );

    $form['recipes']['glass_size'] = array(
      '#type' => 'number',
      '#title' => $this->t('Glass size'),
      '#default_value' => $config->get('glass_size') ? : '',
      '#description' => $this->t('What is the size of one full glass? In milliliter.')
    );

    $form['github'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Github'),
    );

    $form['github']['github_token'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Github token'),
      '#default_value' => $config->get('github_token') ? : '',
      '#description' => $this->t('Generate a token at https://github.com/settings/tokens')
    );

    $form['github']['github_recipe'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Github recipe'),
      '#default_value' => $config->get('github_recipe') ? : 'https://github.com/hsbxl/gnuwhine',
      '#description' => $this->t('The github URI of the genesis recipe.')
    );

    $form['actions']['clearstats'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Clear stats'),
    );


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $triggering_element = $form_state->getTriggeringElement();
    if($triggering_element['#id'] == 'edit-clearstats') {
      $this->gnuwhine->resetStats();
      return;
    }

    $this->config('gnuwhine_ui.settings')
      ->set('ingredients', $form_state->getValue('ingredients'))
      ->set('filter_recipes', $form_state->getValue('filter_recipes'))
      ->set('pump1_name', $form_state->getValue('pump1_name'))
      ->set('pump1_timing', $form_state->getValue('pump1_timing'))
      ->set('pump2_name', $form_state->getValue('pump2_name'))
      ->set('pump2_timing', $form_state->getValue('pump2_timing'))
      ->set('pump3_name', $form_state->getValue('pump3_name'))
      ->set('pump3_timing', $form_state->getValue('pump3_timing'))
      ->set('pump4_name', $form_state->getValue('pump4_name'))
      ->set('pump4_timing', $form_state->getValue('pump4_timing'))
      ->set('pump5_name', $form_state->getValue('pump5_name'))
      ->set('pump5_timing', $form_state->getValue('pump5_timing'))
      ->set('pump6_name', $form_state->getValue('pump6_name'))
      ->set('pump6_timing', $form_state->getValue('pump6_timing'))
      ->set('pump7_name', $form_state->getValue('pump7_name'))
      ->set('pump7_timing', $form_state->getValue('pump7_timing'))
      ->set('pump8_name', $form_state->getValue('pump8_name'))
      ->set('pump8_timing', $form_state->getValue('pump8_timing'))
      ->set('github_recipe', $form_state->getValue('github_recipe'))
      ->set('glass_size', $form_state->getValue('glass_size'))
      ->set('github_token', $form_state->getValue('github_token'))
      ->set('github_recipe', $form_state->getValue('github_recipe'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}