<?php
namespace Doldenroller\Translationstatus;

use Kirby\Form\Field\DisplayField;
use Kirby\Reflection\Attributes\Prop;
use Kirby\Toolkit\Str;

class TranslationstatusField extends DisplayField
{
  /**
   * Text shown when no page links here
   */
  #[Prop]
  protected array|string|null $empty = null;

  /**
   * Text shown above finished translations
   */
  #[Prop]
  protected array|string|null $finished = null;

  #[Prop]
  protected array|string $ignore;

  #[Prop]
  protected array|string|bool $delete;

  #[Prop]
  protected bool $template = true;

  #[Prop]
  protected bool $extend;

  public function finished(): string|null
  {
    // translates `{ en: …, de: … }` arrays and
    // resolves `{{ page.title }}` placeholders
    return $this->stringTemplateI18n($this->finished) ?? t('translations.finished', 'Finished');
  }

  #[Prop]
  protected array|string|null $unfinished = null;

  public function unfinished(): string|null
  {
    // translates `{ en: …, de: … }` arrays and
    // resolves `{{ page.title }}` placeholders
    return $this->stringTemplateI18n($this->unfinished) ?? t('translations.unfinished', 'To be done');
  }

  #[Prop]
  protected array|string|null $allfinished = null;

  public function allfinished(): string|null
  {
    // translates `{ en: …, de: … }` arrays and
    // resolves `{{ page.title }}` placeholders
    return $this->stringTemplateI18n($this->allfinished) ?? t('translations.allfinished', 'All Finished');
  }


  public function api(): array
  {
    $field = $this;

    return [
      [
        'pattern' => '',
        'method'  => 'GET',
        'action'  => fn () => [
          'translations' => $field->translations(),
          'templateName' => $field->templateName(),
        ]
      ]
    ];
  }



  public function translations(): array
  {
    $kirby = kirby();
    $page = $this->model();

    if($kirby->languages()->isNotEmpty()){
      $userrole = $kirby->user()->role()->id();
      $useremail = $kirby->user()->email();

      // on default translations can be deleted
      $deleteable = true;

      // check for restrictions to delete a translation (blueprint beats config)
      $delete = $this->delete ?? $kirby->option('doldenroller.translations.delete');
      if($delete !== null || (is_bool($delete) && $delete === false)){

        // completly disabled
        if(is_bool($delete) && $delete === false){
          $deleteable = false;
        }elseif(!empty($delete)){
          if(!is_array($delete)) $delete = Str::split($delete);
          if(!(in_array($useremail, $delete) || in_array($userrole, $delete)) ){
            $deleteable = false;
          }
        }

      }

      $finishedLang = $unfinishedLang = [];
      foreach(kirby()->languages() as $lang){

        $title = tt('translations.language.switch', null, ['language' => $lang->name()]);

        // check for default language
        $notdefault = true;
        if($lang->isDefault()){
          $notdefault = false;
        }

        if($page->translation($lang->code())->exists()){
          $finishedLang[] = [
            'code' => $lang->code(),
            'name' => $lang->name(),
            'deleteable' => $deleteable,
            'notdefault' => $notdefault,
            'title' => $title
          ];

        }else{
          $unfinishedLang[] = [
            'code' => $lang->code(),
            'name' => $lang->name(),
            'title' => $title
          ];
        }
      }
      $translations = [
        'finished' => $finishedLang,
        'unfinished' => $unfinishedLang,
        'show' => true
      ];

      // ignore templates
      if($ignore = $this->ignore ?? $kirby->option('doldenroller.templatestatus.ignore')){
        if(!is_array($ignore)) $ignore = Str::split($ignore);
        if(in_array($page->intendedTemplate(), $ignore)){
          $translations = [
            'finished' => [],
            'unfinished' => [],
            'show' => false
          ];
        }
      }
    }else{
      // hide evrything except for the template when it is a single language page
      $translations = [
        'finished' => [],
        'unfinished' => [],
        'show' => false
      ];
    }

    return $translations;

  }

  public function templateName(): array
  {
    $page = $this->model();
    $templateName = [
      'name' => $page->blueprint()->title(),
      'icon' => $page->blueprint()->icon()
    ];
    return $templateName;
  }

}
