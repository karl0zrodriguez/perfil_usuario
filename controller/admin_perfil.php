<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_model('perfil.php');
require_model('perfil_access.php');


class admin_perfil extends fs_controller
{
   public $sperfil;
   public $s_p_accesses;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Perfil Usuario', 'admin', TRUE, FALSE);
   }
   
   public function process()
   {
      $this->show_fs_toolbar = FALSE;
      $this->ppage = $this->page->get('admin_perfiles');
      $this->s_p_accesses = new perfil_access();
      $user_no_more_admin = FALSE;
      $this->sperfil = FALSE;
      if( isset($_GET['idperfil']) )
      {
         $perfil= new perfil();
         $this->sperfil = $perfil->get_perfil($_GET['idperfil']);
      }
      
      if($this->sperfil)
      {
         $this->page->title = $this->sperfil->perfil;
         
         if( isset($_POST['snombre']) OR isset($_POST['sdescripcion']) )
         {
            $this->sperfil->idperfil = $_GET['idperfil'];
            $this->sperfil->perfil = $_POST['snombre'];
            $this->sperfil->descripcion = $_POST['sdescripcion'];
            if( $this->sperfil->save() )
            {
                foreach($this->all_pages() as $p)
                {
                   $a = new perfil_access( array('pa_idperfil'=> $this->sperfil->idperfil, 'pa_page'=>$p->name) );

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
               $this->new_message("Datos modificados correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible modificar los datos!");
         }
      }
      else
         $this->new_error_msg("Perfil no encontrado.");
   }
   
   public function url()
   {
      if( !isset($this->sperfil) )
      {
         return parent::url_perfil();
      }
      else if($this->sperfil)
      {
         return $this->sperfil->url_perfil();
      }
      else
         return $this->page->url_perfil();
   }
   
   public function all_pages()
   {
      $returnlist = array();
      foreach($this->menu as $m)
      {
         $m->enabled = FALSE;
         $returnlist[] = $m;
      }
      
      $access = $this->sperfil->get_p_accesses();
      foreach($returnlist as $i => $value)
      {
         foreach($access as $a)
         {
            if($value->name == $a->pa_page)
            {
               $returnlist[$i]->enabled = TRUE;
               break;
            }
         }
      }
      return $returnlist;
   }
   
   
}
