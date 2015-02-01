<?php

require_model ('perfil_access.php');

class perfil extends fs_model
{
    public $idperfil;
    public $perfil;
    public $descripcion;
        
    public function __construct($pu=FALSE) {
        parent::__construct('perfil', 'plugins/perfil_usuario/');
        if($pu){
            $this->idperfil=$pu['idperfil'];
            $this->perfil=$pu['perfil'];
            $this->descripcion=$pu['descripcion'];
        }else{
            $this->idperfil=NULL;
            $this->perfil=NULL;
            $this->descripcion=NULL;
        }
    }
    
    protected function install() {
        ;
    }
    public function test() {
        ;
    }
    
    public function delete() {
        return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idperfil = ".$this->var2str($this->idperfil).";");
    }
    
    public function exists() {
        $p_u=$this->db->select("select * from perfil where idperfil=".$this->var2str($this->idperfil)." ;");
        if($p_u){
            return TRUE;
        }else
            return FALSE;
    }
    
    public function save() {
        if($this->exists())
        {
            $sql="UPDATE perfil SET perfil=".$this->var2str($this->perfil)." ,descripcion=".$this->var2str($this->descripcion)." WHERE idperfil= ".$this->var2str($this->idperfil).";";
        }else{
            $this->new_idperfil();
            $sql="INSERT INTO perfil (idperfil,perfil,descripcion)
             VALUES (".$this->var2str($this->idperfil).",".$this->var2str($this->perfil).",".$this->var2str($this->descripcion).");";
        }
        return $this->db->exec($sql);
    }
    
    public function get_perfil($n = '')
   {
      $u = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idperfil = ".$this->var2str($n).";");
      if($u)
      {
         return new perfil($u[0]);
      }
      else
         return FALSE;
   }
   
   public function url_perfil()
   {
      if( is_null($this->idperfil) )
      {
         return 'index.php?page=admin_perfiles';
      }
      else
         return 'index.php?page=admin_perfil&idperfil='.$this->idperfil;
   }
   
   public function new_idperfil()
   {
      $newid = $this->db->nextval($this->table_name.'_idperfil_seq');
      if($newid)
         $this->idperfil = intval($newid);
   }
   
   public function perfil_all()
   {
      $perfillist = $this->cache->get_array('m_perfil_all');
      
      if(!$perfillist)
      {
         $users = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY perfil ASC;");
         if($users)
         {
            foreach($users as $u)
               $perfillist[] = new perfil($u);
         }
         $this->cache->set('m_perfil_all', $perfillist);
      }
      
      return $perfillist;
   }

   public function get_p_accesses()
   {
      $access = new perfil_access();
      return $access->all_from_perfil($this->idperfil);
   }
}
