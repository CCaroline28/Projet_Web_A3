<?php
  
  require_once('constantes.php');

// sert à se connecter à la base de données
// Retourne un objet PDO en cas de succès ou false en cas d'échec
function dbConnect(){
  try{
    $db = new PDO('mysql:host='.DB_SERVER.';dbname='.DB_NAME.';charset=utf8;'.
    'port='.DB_PORT, DB_USER, DB_PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }
  catch (PDOException $exception){
    error_log('Connection error: '.$exception->getMessage());
    return false;
  }
  return $db;
}


function createdatabase($db){
  try
  {
  $request = '
  CREATE TABLE mois_install(
        mois Varchar (50) NOT NULL
	,CONSTRAINT mois_install_PK PRIMARY KEY (mois)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: annee_install
#------------------------------------------------------------

CREATE TABLE annee_install(
        annee Int NOT NULL
	,CONSTRAINT annee_install_PK PRIMARY KEY (annee)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: onduleur
#------------------------------------------------------------

CREATE TABLE onduleur(
        id     Int  Auto_increment  NOT NULL ,
        marque Varchar (50) NOT NULL ,
        modele Varchar (50) NOT NULL
	,CONSTRAINT onduleur_PK PRIMARY KEY (id)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: parametres
#------------------------------------------------------------

CREATE TABLE parametres(
        id                  Int  Auto_increment  NOT NULL ,
        puissance_crete     Int NOT NULL ,
        surface             Int NOT NULL ,
        pente               Int NOT NULL ,
        pente_optimum       Int NOT NULL ,
        orientation         Int NOT NULL ,
        orientation_optimum Int NOT NULL
	,CONSTRAINT parametres_PK PRIMARY KEY (id)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: panneau
#------------------------------------------------------------

CREATE TABLE panneau(
        id            Int  Auto_increment  NOT NULL ,
        marque        Varchar (50) NOT NULL ,
        modele        Varchar (50) NOT NULL ,
        produc_pvgis  Int NOT NULL ,
        id_parametres Int NOT NULL
	,CONSTRAINT panneau_PK PRIMARY KEY (id)

	,CONSTRAINT panneau_parametres_FK FOREIGN KEY (id_parametres) REFERENCES parametres(id)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: installateur
#------------------------------------------------------------

CREATE TABLE installateur(
        nom Varchar (50) NOT NULL
	,CONSTRAINT installateur_PK PRIMARY KEY (nom)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: _pays
#------------------------------------------------------------

CREATE TABLE _pays(
        pays Varchar (50) NOT NULL
	,CONSTRAINT _pays_PK PRIMARY KEY (pays)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: _region
#------------------------------------------------------------

CREATE TABLE _region(
        pays   Varchar (50) NOT NULL ,
        code   Int NOT NULL ,
        region Varchar (50) NOT NULL
	,CONSTRAINT _region_PK PRIMARY KEY (pays,code)

	,CONSTRAINT _region__pays_FK FOREIGN KEY (pays) REFERENCES _pays(pays)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: _departement
#------------------------------------------------------------

CREATE TABLE _departement(
        pays         Varchar (50) NOT NULL ,
        code__region Int NOT NULL ,
        code         Int NOT NULL ,
        departement  Varchar (50) NOT NULL
	,CONSTRAINT _departement_PK PRIMARY KEY (pays,code__region,code)

	,CONSTRAINT _departement__region_FK FOREIGN KEY (pays,code__region) REFERENCES _region(pays,code)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: _code_postal
#------------------------------------------------------------

CREATE TABLE _code_postal(
        pays              Varchar (50) NOT NULL ,
        code__region      Int NOT NULL ,
        code__departement Int NOT NULL ,
        code_postal       Varchar (10) NOT NULL ,
        code_postal_suff  Varchar (10) NOT NULL ,
        postal_town       Varchar (50) NOT NULL
	,CONSTRAINT _code_postal_PK PRIMARY KEY (pays,code__region,code__departement,code_postal)

	,CONSTRAINT _code_postal__departement_FK FOREIGN KEY (pays,code__region,code__departement) REFERENCES _departement(pays,code__region,code)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: commune
#------------------------------------------------------------

CREATE TABLE commune(
        pays              Varchar (50) NOT NULL ,
        code__region      Int NOT NULL ,
        code__departement Int NOT NULL ,
        code_postal       Varchar (10) NOT NULL ,
        code_INSEE        Varchar (10) NOT NULL ,
        nom               Varchar (10) NOT NULL ,
        population        Int NOT NULL
	,CONSTRAINT commune_PK PRIMARY KEY (pays,code__region,code__departement,code_postal,code_INSEE)

	,CONSTRAINT commune__code_postal_FK FOREIGN KEY (pays,code__region,code__departement,code_postal) REFERENCES _code_postal(pays,code__region,code__departement,code_postal)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: installation
#------------------------------------------------------------

CREATE TABLE installation(
        ID                Int  Auto_increment  NOT NULL ,
        iddoc             Int NOT NULL ,
        nb_panneau        Int NOT NULL ,
        nb_onduleur       Int NOT NULL ,
        nom               Varchar (50) NOT NULL ,
        mois              Varchar (50) NOT NULL ,
        pays              Varchar (50) NOT NULL ,
        code__region      Int NOT NULL ,
        code__departement Int NOT NULL ,
        code_postal       Varchar (10) NOT NULL ,
        code_INSEE        Varchar (10) NOT NULL
	,CONSTRAINT installation_PK PRIMARY KEY (ID)

	,CONSTRAINT installation_installateur_FK FOREIGN KEY (nom) REFERENCES installateur(nom)
	,CONSTRAINT installation_mois_install0_FK FOREIGN KEY (mois) REFERENCES mois_install(mois)
	,CONSTRAINT installation_commune1_FK FOREIGN KEY (pays,code__region,code__departement,code_postal,code_INSEE) REFERENCES commune(pays,code__region,code__departement,code_postal,code_INSEE)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: installe1
#------------------------------------------------------------

CREATE TABLE installe1(
        id              Int NOT NULL ,
        ID_installation Int NOT NULL
	,CONSTRAINT installe1_PK PRIMARY KEY (id,ID_installation)

	,CONSTRAINT installe1_panneau_FK FOREIGN KEY (id) REFERENCES panneau(id)
	,CONSTRAINT installe1_installation0_FK FOREIGN KEY (ID_installation) REFERENCES installation(ID)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table: installe
#------------------------------------------------------------

CREATE TABLE installe(
        ID          Int NOT NULL ,
        id_onduleur Int NOT NULL
	,CONSTRAINT installe_PK PRIMARY KEY (ID,id_onduleur)

	,CONSTRAINT installe_installation_FK FOREIGN KEY (ID) REFERENCES installation(ID)
	,CONSTRAINT installe_onduleur0_FK FOREIGN KEY (id_onduleur) REFERENCES onduleur(id)
)ENGINE=InnoDB;


#------------------------------------------------------------
# Table:année
#------------------------------------------------------------

CREATE TABLE de_l_annee(
        annee Int NOT NULL ,
        mois  Varchar (50) NOT NULL
	,CONSTRAINT de_l_annee_PK PRIMARY KEY (annee,mois)

	,CONSTRAINT de_l_annee_annee_install_FK FOREIGN KEY (annee) REFERENCES annee_install(annee)
	,CONSTRAINT de_l_annee_mois_install0_FK FOREIGN KEY (mois) REFERENCES mois_install(mois)
)ENGINE=InnoDB;';
  $statement = $db->prepare($request);
  $statement->execute();
  $result = $statement->fetchAll(PDO::FETCH_ASSOC); 
  }
  catch (PDOException $exception)
  {
  error_log('Request error: '.$exception->getMessage());
  return false;
  }
  return $result;
}


?>