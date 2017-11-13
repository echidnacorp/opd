<?php
/**
 * @file
 * Contains \Drupal\obd_search\Form\SearchForm.
 */
namespace Drupal\obd_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['keywords'] = array(
      '#type' => 'textfield',
      '#size' => 60,
      '#maxlength' => 60,
      '#attributes' => array('placeholder' => $this->t('Search')),
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type'   => 'submit',
      '#value'  => $this->t('Search'),
      '#name'   => 'Search',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $values = $form_state->getValues();
    if ($language == 'en') {
      if (isset($values['keywords']) && !empty(trim($values['keywords']))) {
        $url = $this->url('view.search_view_english.page_1', array(), array('query' => array('search_api_fulltext' => $values['keywords'])));
      }
      else {
        $url = $this->url('view.search_view_english.page_1', array());
      }
    }
    else {
      if (isset($values['keywords']) && !empty(trim($values['keywords']))) {
        $url = $this->url('view.search_view_french.page_1', array(), array('query' => array('search_api_fulltext' => $values['keywords'])));
      }
      else {
        $url = $this->url('view.search_view_french.page_1', array());
      }
    }
    $response = new RedirectResponse($url);
    $response->send();
  }

}
