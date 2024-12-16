<?php
namespace PTA\API\REST\utils;

class RouteRegistrar
{
  public static function register_routes($restv2_instance)
  {
    /* This is the route for getting submissions */
    register_rest_route('pta/v2', '/submission', array(
      'methods' => 'GET',
      'callback' => [$restv2_instance, 'submission_get'],
      'permission_callback' => [$restv2_instance, 'check_permissions'],
    ));

    /* Route for getting contest information */
    register_rest_route('pta/v2', '/contest/info', array(
      'methods' => 'GET',
      'callback' => [$restv2_instance, 'get_contest_info'],
      'permission_callback' => [$restv2_instance, 'check_permissions'],
    ));

    /* This is the route for deleting,rejecting,unrejecting,approve submissions */
    register_rest_route('pta/v2', '/submission/action', array(
      'methods' => 'POST',
      'callback' => [$restv2_instance, 'submission_action'],
      'permission_callback' => [$restv2_instance, 'check_permissions'],
    ));

    //   /* This is the route for editing submissions */
    //   // register_rest_route('pta/v2', '/submission', array(
    //   //   'methods' => 'POST',
    //   //   'callback' => [$this, 'submission_edit'],
    //   //   'permission_callback' => [$this, 'check_permissions'],
    //   // ));
  }
}