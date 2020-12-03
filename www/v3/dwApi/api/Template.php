<?php
namespace dwApi\api;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use voku\helper\URLify;


/**
 * Class Template
 * @package dwApi\api
 */
class Template {

  const TEMPLATE_PATH = __DIR__ . '/../../../templates';

  /**
   * renderTwigString.
   * @param $string
   * @param $variables
   * @return string
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   */
  public static function renderTwigString($string, $variables) {
    $loader = new \Twig\Loader\ArrayLoader();
    $twig = new Environment($loader);
    return $twig->render(
      $twig->createTemplate($string),
      $variables
    );
  }


  /**
   * renderTwigFile.
   * @param $template
   * @param $variables
   * @return string
   * @throws \Twig\Error\LoaderError
   * @throws \Twig\Error\RuntimeError
   * @throws \Twig\Error\SyntaxError
   */
  public static function renderTwigFile($template, $variables) {
    $loader = new FilesystemLoader(self::TEMPLATE_PATH);
    $twig = new Environment($loader,[
      'debug' => true]);
    $twig->addExtension(new \Twig\Extension\DebugExtension());

    return $twig->render(
      $template.".html.twig",
      $variables);
  }

  /**
   * templateArray.
   * @param $element
   * @param $project
   * @param $action
   * @param string $entity
   * @return array
   */
  public static function templateArray($element, $project, $action, $entity = "") {
    return array(
      $project."/".URLify::filter($element)."--".URLify::filter($action)."--".URLify::filter(strval($entity)),
      $project."/".URLify::filter($element)."--".URLify::filter($action),
      $project."/".URLify::filter($element),
      URLify::filter($element)."--".URLify::filter($action)."--".URLify::filter(strval($entity)),
      URLify::filter($element)."--".URLify::filter($action),
      URLify::filter($element));
  }

  /**
   * pickTemplate.
   * @param $element
   * @param $project
   * @param $action
   * @param $entity
   * @return bool|mixed
   */
  public static function pickTemplate($element, $project, $action, $entity) {
    $template_array = self::templateArray($element, $project, $action, $entity);
    foreach($template_array as $template) {
      if (file_exists(self::TEMPLATE_PATH."/".$template.".html.twig")) {
        return $template;
      }
    }
    return false;
  }
}