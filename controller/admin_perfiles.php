<?php

require_model('perfil.php');

class admin_perfiles extends fs_controller
{
   public $perfil;
      
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Perfiles Usuario', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      $this->perfil = new perfil();
      $x_perfil = new perfil();
      if( isset($_POST['nperfil']) )
      {
         $nu = $x_perfil->get_perfil($_POST['nperfil']);
         if($nu)
         {
            Header( 'location: '.$nu->url_perfil() );
         }
         else
         {
            $nu = new perfil();
            $nu->perfil = $_POST['nperfil'];
            $nu->descripcion = $_POST['ndescripcion'];
               if( $nu->save() )
               {
                  Header('location: index.php?page=admin_perfil&idperfil=' . $nu->idperfil);
               }
               else
                  $this->new_error_msg("¡Imposible guardar el perfil!");
         }
      }
      else if( isset($_GET['delete']) )
      {
         $nu = $x_perfil->get_perfil($_GET['delete']);
         if($nu)
         {
            if( $nu->delete() )
            {
               $this->new_message("Perfil ".$nu->idperfil." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar al perfil!");
         }
         else
            $this->new_error_msg("¡Perfil no encontrado!");
      }
   }
}
