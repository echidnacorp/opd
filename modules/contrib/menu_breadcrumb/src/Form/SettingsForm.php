<?php

namespace Drupal\menu_breadcrumb\Form;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Services used by the form generator.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Used to obtain configuration information.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Used to determined whether the Menu UI module is installed.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $moduleHandler
  ) {
    parent::__construct($config_factory);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['menu_breadcrumb.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('menu_breadcrumb.settings');
    // Renamed from the now meaningless option "determine_menu":
    $form['determine_menu'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the Menu Breadcrumb module'),
      '#description' => $this->t(
      'Use menu the page belongs to, or the page for the taxonomy of which it is a member, for the breadcrumb. If unset, no breadcrumbs are generated or cached by this module and all settings below are ignored.'
      ),
      '#default_value' => $config->get('determine_menu'),
    ];

    $form['disable_admin_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable for admin pages'),
      '#description' => $this->t('Do not build menu-based breadcrumbs for admin pages.'),
      '#default_value' => $config->get('disable_admin_page'),
    ];

    $form['append_current_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Append current page to breadcrumb'),
      '#description' => $this->t('If current page is on a menu, include it in the breadcrumb trail.'),
      '#default_value' => $config->get('append_current_page'),
    ];

    $form['current_page_as_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show current page as link'),
      '#description' => $this->t('Set TRUE if the current page in the breadcrumb trail should be a link (otherwise it will be plain text).'),
      '#default_value' => $config->get('current_page_as_link'),
      '#states' => [
        'visible' => [
          ':input[name="append_current_page"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['append_member_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Attach taxonomy member page to breadcrumb'),
      '#description' => $this->t('This option affects breadcrumb display when the current page is a member of a taxonomy whose term is on a menu with "Taxonomy Attachment" selected, when it "attaches" to the menu-based breadcrumbs of that taxonomy term. In this case that term\'s menu title will show as a link regardless of the "current page" options above. Set this option TRUE to also show the the current ("attached") <i>page</i> title as the final breadcrumb.'),
      '#default_value' => $config->get('append_member_page'),
    ];

    $form['member_page_as_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show attached current page as link'),
      '#description' => $this->t('Set TRUE to show the attached final breadcrumb as a link (otherwise it will be plain text).'),
      '#default_value' => $config->get('member_page_as_link'),
      '#states' => [
        'visible' => [
          ':input[name="append_member_page"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Removed option "hide_on_single_item" - makes no sense when the taxonomy
    // attachment feature is added, especially now that this module reverts to
    // other breadcrumb builders (e.g., the path-based system breadcrumb) when
    // it doesn't apply (can be reconsidered if there is a valid use case).
    // $form['hide_on_single_item'] ...
    //
    $form['remove_home'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove "Home" link'),
      '#default_value' => $config->get('remove_home'),
      '#description' => $this->t('Regardless of option settings, this module always checks if the first breadcrumb is also the &lt;front&gt; page. Without this option set, it will always replace the link for that node- or view- based path of the &lt;front&gt; page (e.g., /node/1 or /node) with a link to the site home. Set this option TRUE to <i>delete</i> the &lt;front&gt; breadcrumb rather than replacing it.'),
    ];

    $form['add_home'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add "Home" link'),
      '#default_value' => $config->get('add_home'),
      '#description' => $this->t('If TRUE will add a link to the &lt;front&gt; page if it doesn\'t already begin the breadcrumb trail: ensuring that the first breadcrumb of every page is the site home. If both "add" and "remove" are set, when displaying the &lt;front&gt; page and its menu children the "remove" option will take precedence.'),
    ];

    $form['home_as_site_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use site name instead of "Home" link'),
      '#description' => $this->t('Uses the site name from the configuration settings: if this option is not set, a translated value for "Home" will be used.'),
      '#default_value' => $config->get('home_as_site_name'),
    ];

    $form['include_exclude'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Enable / Disable Menus'),
      '#description' => $this->t('<b>Order of operation:</b> The breadcrumb will be generated from the first match it finds: "Enabled" to look for the current item on the menu, then "Taxonomy Attachment" to look for its taxonomy term. Re-order the list to change the priority of each menu.<br><b>Language Handling:</b> If set, skip this menu when the defined menu language does not match the current content language: recommended setting when you use a separate menu per language. This has no effect on taxonomy attachment.'),
    ];
    $form['include_exclude']['note_about_navigation'] = [
      '#markup' => '<p class="description">' . $this->t("Note: If none of the selected menus contain an item for a given page, Drupal will look in the 'Navigation' menu by default, even if it is 'disabled' here.") . '</p>',
    ];

    // Orderable list of menu selections.
    $form['include_exclude']['menu_breadcrumb_menus'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Menu'),
        $this->t('Enabled'),
        $this->t('Taxonomy Attachment'),
        $this->t('Language Handling'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('There are no menus yet.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'menus-order-weight',
        ],
      ],
    ];

    foreach ($this->getSortedMenus() as $menu_name => $menu_config) {

      $form['include_exclude']['menu_breadcrumb_menus'][$menu_name] = [
        '#attributes' => [
          'class' => ['draggable'],
        ],
        '#weight' => $menu_config['weight'],
        'title' => [
          '#plain_text' => $menu_config['label'],
        ],
        'enabled' => [
          '#type' => 'checkbox',
          '#default_value' => $menu_config['enabled'],
        ],
        'taxattach' => [
          '#type' => 'checkbox',
          '#default_value' => $menu_config['taxattach'],
        ],
        'langhandle' => [
          '#type' => 'checkbox',
          '#default_value' => $menu_config['langhandle'],
        ],
        'weight' => [
          '#type' => 'weight',
          '#default_value' => $menu_config['weight'],
          '#attributes' => ['class' => ['menus-order-weight']],
        ],
      ];
    }
    // Removed description of a "Default setting" selection which was never
    // implemeneted in the current D8 version of Menu Breadcrumb.
    // TODO perhaps find out if this option would be worth preserving
    // (applied to default tick boxes for new menus added in the future).
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('menu_breadcrumb.settings')
      ->set('determine_menu', (boolean) $form_state->getValue('determine_menu'))
      ->set('disable_admin_page', (boolean) $form_state->getValue('disable_admin_page'))
      ->set('append_current_page', (boolean) $form_state->getValue('append_current_page'))
      ->set('current_page_as_link', (boolean) $form_state->getValue('current_page_as_link'))
      ->set('append_member_page', (boolean) $form_state->getValue('append_member_page'))
      ->set('member_page_as_link', (boolean) $form_state->getValue('member_page_as_link'))
      ->set('home_as_site_name', (boolean) $form_state->getValue('home_as_site_name'))
      ->set('remove_home', (boolean) $form_state->getValue('remove_home'))
      ->set('add_home', (boolean) $form_state->getValue('add_home'))
      ->set('menu_breadcrumb_menus', $form_state->getValue('menu_breadcrumb_menus'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_breadcrumb_settings';
  }

  /**
   * Get Sorted Menus.
   *
   * Returns array of menus with properties (enabled, taxattach, langhandle,
   * weight, label) sorted by weight, initializing those properties if needed.
   */
  protected function getSortedMenus() {
    $menu_enabled = $this->moduleHandler->moduleExists('menu_ui');
    $menus = $menu_enabled ? menu_ui_get_menus() : menu_list_system_menus();
    $menu_breadcrumb_menus = $this->config('menu_breadcrumb.settings')->get('menu_breadcrumb_menus');

    foreach ($menus as $menu_name => &$menu) {
      if (!empty($menu_breadcrumb_menus[$menu_name])) {
        $menu = $menu_breadcrumb_menus[$menu_name] + ['label' => $menu];
        // Earlier versions of the module might not have these array keys set.
        // TODO Maybe set these for existing menu definitions in upgrade script?
        if (!isset($menu['taxattach'])) {
          $menu['taxattach'] = 0;
        }
        if (!isset($menu['langhandle'])) {
          $menu['langhandle'] = 0;
        }
      }
      else {
        $menu = [
          'weight' => 0,
          'enabled' => 0,
          'taxattach' => 0,
          'langhandle' => 0,
          'label' => $menu,
        ];
      }
    }
    uasort($menus, function ($a, $b) {
      return SortArray::sortByWeightElement($a, $b);
    });
    return $menus;
  }

}
