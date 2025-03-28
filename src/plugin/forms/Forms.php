<?php
namespace PTA\forms;

/* Prevent direct access */
if (!defined('ABSPATH')) {
  exit;
}

use PTA\client\Client;
use PTA\forms\integrations\KadenceBlocksPTA;

class Forms extends Client
{
  private KadenceBlocksPTA $kadence_blocks_integration;

  public function __construct()
  {
    parent::__construct(LogName: "Forms", callback_after_init: $this->setup());
  }

  public function setup()
  {
    $this->kadence_blocks_integration = new KadenceBlocksPTA(forms: $this);
    if(!$this->kadence_blocks_integration) {
      return;
    }
    $this->kadence_blocks_integration->register_routes();
  }
}