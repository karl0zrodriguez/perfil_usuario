<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013-2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('ejercicio.php');
require_model('perfil.php');

class admin_user extends fs_controller
{
   public $agente;
   public $ejercicio;
   public $user_log;
   public $suser;
   public $perfil;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Usuario', 'admin', TRUE, FALSE);
   }
   
   public function process()
   {
      $this->share_extensions();
      
      $this->agente = new agente();
      $this->ejercicio = new ejercicio();
      $this->perfil = new perfil();
      $user_no_more_admin = FALSE;
      
      $this->suser = FALSE;
      if( isset($_GET['snick']) )
      {
         $this->suser = $this->user->get($_GET['snick']);
      }
      
      if($this->suser)
      {
         $this->page->title = $this->suser->nick;
         
         if( isset($_POST['nnombre']) )
         {
            $age0 = new agente();
            $age0->codagente = $age0->get_new_codigo();
            $age0->nombre = $_POST['nnombre'];
            $age0->apellidos = $_POST['napellidos'];
            $age0->dnicif = $_POST['ndnicif'];
            $age0->telefono = $_POST['ntelefono'];
            $age0->email = $_POST['nemail'];
            if( $age0->save() )
            {
               $this->new_message("Empleado ".$age0->codagente." guardado correctamente.");
               $this->suser->codagente = $age0->codagente;
               
               if( $this->suser->save() )
               {
                  $this->new_message("Empleado ".$age0->codagente." asignado correctamente.");
               }
               else
                  $this->new_error_msg("¡Imposible asignar el agente!");
            }
            else
               $this->new_error_msg("¡Imposible guardar el agente!");
         }
         else if( isset($_POST['spassword']) OR isset($_POST['scodagente']) OR isset($_POST['sadmin']) )
         {
            if($_POST['spassword'] != '')
            {
               $this->suser->set_password($_POST['spassword']);
            }
            
            if( isset($_POST['scodagente']) )
            {
               $this->suser->codagente = NULL;
               if($_POST['scodagente'] != '')
               {
                  $this->suser->codagente = $_POST['scodagente'];
               }
            }
            
            /*
             * El propio usuario no puede decidir dejar de ser administrador.
             */
            if($this->user->nick != $this->suser->nick)
            {
               /*
                * Si un usuario es administrador y deja de serlo, hay que darle acceso
                * a algunas páginas, en caso contrario no podrá continuar
                */
               if($this->suser->admin AND !isset($_POST['sadmin']))
               {
                  $user_no_more_admin = TRUE;
               }
               
               $this->suser->admin = isset($_POST['sadmin']);
            }
            
            $this->suser->fs_page = NULL;
            if( isset($_POST['udpage']) )
            {
               $this->suser->fs_page = $_POST['udpage'];
            }
            
            if( isset($_POST['css']) )
            {
               $this->suser->css = $_POST['css'];
            }
            
            $this->suser->codejercicio = NULL;
            if( isset($_POST['ejercicio']) )
            {
               $this->suser->codejercicio = $_POST['ejercicio'];
            }
            
            if(FS_DEMO AND $this->user->nick != $this->suser->nick)
            {
               $this->new_error_msg('En el modo <b>demo</b> sólo puedes modificar los datos de TU usuario.
                  Esto es así para evitar malas prácticas entre usuarios que prueban la demo.');
               $this->suser = $this->user->get($_GET['snick']);
            }
            else if( $this->suser->save() )
            {
               if( !$this->suser->admin )
               {
                  foreach($this->all_pages() as $p)
                  {
                     $a = new fs_access( array('fs_user'=> $this->suser->nick, 'fs_page'=>$p->name) );
                     
                     if( $user_no_more_admin )
                     {
                        $a->save();
                     }
                     else if( !isset($_POST['enabled']) )
                     {
                        $a->delete();
                     }
                     else if( !$p->enabled AND in_array($p->name, $_POST['enabled']) )
                     {
                        $a->save();
                     }
                     else if( $p->enabled AND !in_array($p->name, $_POST['enabled']) )
                     {
                        $a->delete();
                     }
                  }
               }
               
               $this->new_message("Datos modificados correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible modificar los datos!");
         }
         
         /// ¿Estamos modificando nuestro usuario?
         if($this->suser->nick == $this->user->nick)
         {
            $this->user = $this->suser;
         }
         
         /// si el usuario no tiene acceso a ninguna página, entonces hay que informar del problema.
         if( !$this->suser->admin )
         {
            $sin_paginas = TRUE;
            foreach($this->all_pages() as $p)
            {
               if($p->enabled)
               {
                  $sin_paginas = FALSE;
                  break;
               }
            }
            if($sin_paginas)
            {
               $this->new_advice('No has autorizado a este usuario a acceder a ninguna'
                  . ' página y por tanto no podrá hacer nada. Puedes darle acceso a alguna página'
                  . ' desde el panel de más abajo.');
            }
         }
         
         $fslog = new fs_log();
         $this->user_log = $fslog->all_from($this->suser->nick);
      }
      else
         $this->new_error_msg("Usuario no encontrado.");
   }
   
   public function url()
   {
      if( !isset($this->suser) )
      {
         return parent::url();
      }
      else if($this->suser)
      {
         return $this->suser->url();
      }
      else
         return $this->page->url();
   }
   
   public function all_pages()
   {
      $returnlist = array();
      foreach($this->menu as $m)
      {
         $m->enabled = FALSE;
         $returnlist[] = $m;
      }
      
      $access = $this->suser->get_accesses();
      foreach($returnlist as $i => $value)
      {
         foreach($access as $a)
         {
            if($value->name == $a->fs_page)
            {
               $returnlist[$i]->enabled = TRUE;
               break;
            }
         }
      }
      return $returnlist;
   }
   
   private function share_extensions()
   {
      $extensions = array(
          array(
              'name' => 'bootstrap',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap.min.css',
              'params' => ''
          ),
          array(
              'name' => 'cosmo',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap-cosmo.min.css',
              'params' => ''
          ),
          array(
              'name' => 'darkly',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap-darkly.min.css',
              'params' => ''
          ),
          array(
              'name' => 'flatly',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap-flatly.min.css',
              'params' => ''
          ),
          array(
              'name' => 'sandstone',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap-sandstone.min.css',
              'params' => ''
          ),
          array(
              'name' => 'united',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap-united.min.css',
              'params' => ''
          ),
          array(
              'name' => 'yeti',
              'page_from' => __CLASS__,
              'page_to' => __CLASS__,
              'type' => 'css',
              'text' => 'view/css/bootstrap-yeti.min.css',
              'params' => ''
          ),
      );
      foreach($extensions as $ext)
      {
         $fsext = new fs_extension($ext);
         $fsext->save();
      }
   }
}
