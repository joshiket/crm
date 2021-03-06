<?php
    class User{
        private $dbArr;
        private $data=array();
        private $table;
        private $view;
        private $messages;

        public function __construct()
        {
            $this->dbArr=parse_ini_file("../db.ini");
            $this->table="tbl_users";
            $this->view = "";
            $this->messages=parse_ini_file("User_msg.ini");
        }

        public function __set($property,$value)
        {
            if($property=="userid"||$property=="email"||$property=="pass"||$property=="cpass"||$property=="npass"||$property=="resetQ"||$property=="resetAns")
                $this->data[$property]=$value;
        }
        
        
        public function __get($property)
        {
            if(array_key_exists($property,$this->data))
                return $this->data[$property];
            else
                return null;
        }

        public function generateResponse($error, $msg, $data)
        {
            $msgArr["error"]=$error;
            if($data)
                $msgArr["data"]=$msg;
            else
                $msgArr["msg"]=$msg;
            return json_encode($msgArr);
        }

        public function encrypt($str)
        {
            $str=strrev($str);
            $encstr=crypt($str,"crm");
            return $encstr;
        }

        public function generatePassword()
        {
            $length = 10;
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
            $password = substr( str_shuffle( $chars ), 0, $length );
            return $password;
        }   

        public function sync_enc($str)
        {
            $str == strrev($str);
            $encstr = "";
            for($i=0;$i < strlen($str); $i++)
            {
                $ch = dechex(ord(substr($str,$i,1)));
                if(strlen($ch)==1)
                {
                    $ch = "0".$ch;
                }
                $encstr = $encstr . $ch;
            }
            return $encstr;
        }
        
        public function userLogin()
        {
            $query = sprintf("SELECT pass FROM %s WHERE email='%s'",$this->table,$this->email);
            try
            {
                $config = array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false);				
                $con = new PDO("mysql:host=".$this->dbArr['dbserver'].";dbname=".$this->dbArr['dbname']."", $this->dbArr['dbuser'], $this->dbArr['dbpass'], $config);	
                $stmt=$con->prepare($query);
                $stmt->execute();
                
                if($stmt->rowCount()>0)
                {
                    $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    $dbpass = $result[0]["pass"];
                    $this->pass = $this->encrypt($this->pass);
                    if($dbpass==$this->pass)
                    {                        
                        session_start();
                        $_SESSION["lguser"] = $this->email;
                        $msgArr["error"] = false;
                        $msgArr["msg"] = $this->messages["logins"];
                        $msgArr["data"] = $this->sync_enc($this->email);
                        $msg = json_encode($msgArr);
                    } 
                    else
                    {
                        $msg = $this->generateResponse(TRUE,$this->messages["loginerr"],FALSE);
                    }                    
                    return $msg;                
                }
                else
                {
                    $msg=$this->generateResponse(TRUE,$this->messages["error"],FALSE);
                    return $msg;                                
                }
            }
            catch(PDOException $e){
                $msg=$this->generateResponse(TRUE,$e->getMessage(),FALSE);
                return $msg;                 
            }
        }

        public function getResetQ()
        {
            $query = sprintf("SELECT resetQ FROM %s WHERE email='%s'",$this->table,$this->email);
            //return $query;
            try
            {
                $config=array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false);				
                $con=new PDO("mysql:host=".$this->dbArr['dbserver'].";dbname=".$this->dbArr['dbname']."", $this->dbArr['dbuser'], $this->dbArr['dbpass'], $config);	
                $stmt=$con->prepare($query);
                $stmt->execute();                
                if($stmt->rowCount()>0)
                {
                    $result=$stmt->fetchAll(PDO::FETCH_ASSOC);                   
                    $msg = $this->generateResponse(FALSE,json_encode($result),TRUE);       
                    return $msg;                             
                }                
            }
            catch(PDOException $e){
                $msg=$this->generateResponse(TRUE,$e->getMessage(),FALSE);
                return $msg;                 
            }
        }

        public function resetPassword()
        {
            $query = sprintf("SELECT resetAns FROM %s WHERE email='%s'",$this->table,$this->email);
            try
            {
                $config=array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false);				
                $con=new PDO("mysql:host=".$this->dbArr['dbserver'].";dbname=".$this->dbArr['dbname']."", $this->dbArr['dbuser'], $this->dbArr['dbpass'], $config);	
                $stmt=$con->prepare($query);
                $stmt->execute();                
                if($stmt->rowCount()>0)
                {
                    $result=$stmt->fetchAll(PDO::FETCH_ASSOC);   
                    $dbresultAns=$result[0]["resetAns"];
                    if($this->resetAns==$dbresultAns)
                    {
                        $this->pass=$this->generatePassword();
                        $query=sprintf("UPDATE %s SET pass='%s' WHERE email='%s'",$this->table,$this->encrypt($this->pass),$this->email);
                        $stmt=$con->prepare($query);
                        $stmt->execute();
                        if($stmt->rowCount()>0)
                        {
                            $data['pass']=$this->pass;
                            $msg=$this->generateResponse(FALSE,json_encode($data),TRUE);
                        }                        
                    }        
                    else
                    {
                        $msg = $this->generateResponse(TRUE,$this->messages["reseterr"],TRUE); 
                    }        
                    
                    return $msg;
                }                
            }
            catch(PDOException $e){
                $msg=$this->generateResponse(TRUE,$e->getMessage(),FALSE);
                return $msg;                 
            }
        }

        public function changePassword()
        {
            $query = sprintf("SELECT pass FROM %s WHERE email='%s'",$this->table,$this->email);
            try
            {
                $config = array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false);				
                $con = new PDO("mysql:host=".$this->dbArr['dbserver'].";dbname=".$this->dbArr['dbname']."", $this->dbArr['dbuser'], $this->dbArr['dbpass'], $config);	
                $stmt=$con->prepare($query);
                $stmt->execute();
                
                if($stmt->rowCount()>0)
                {
                    $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    $dbpass = $result[0]["pass"];
                    $this->cpass = $this->encrypt($this->cpass);
                    $this->npass = $this->encrypt($this->npass);
                    if($dbpass==$this->cpass)
                    {
                        $query = sprintf("UPDATE %s SET pass = '%s' WHERE email='%s'",$this->table,$this->npass,$this->email);
                        $stmt=$con->prepare($query);
                        $stmt->execute();
                        if($stmt->rowCount()>0)
                        {
                            $msg=generateResponse(FALSE,$this->messages["cp"],FALSE);
                        }
                        else
                        {
                            $msg=generateResponse(TRUE,$stmt->errorInfo()[2],FALSE);
                        }
                    } 
                    else
                    {
                        $msg=generateResponse(TRUE,$this->messages["loginerr"],FALSE);
                    }                    
                    return $msg;                
                }
                else
                {
                    $msg=$this->generateResponse(TRUE,$this->messages["error"],FALSE);
                    return $msg;                                
                }
            }
            catch(PDOException $e){
                $msg=$this->generateResponse(TRUE,$e->getMessage(),FALSE);
                return $msg;                 
            }
        }

        public function getUser($field_list="*",$where = "", $order = "", $limit="")
        {
            $query="select ".$field_list." from ".$this->table;
            if($where != "")
                $query.= " where ".$where." ";
            if($order!="")
                $query.=$order." ";
            if($limit!="")
                $query.=$limit."";
            //return $query;
            try
            {
                $config = array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false);				
                $con = new PDO("mysql:host=".$this->dbArr['dbserver'].";dbname=".$this->dbArr['dbname']."", $this->dbArr['dbuser'], $this->dbArr['dbpass'], $config);	
                $stmt=$con->prepare($query);
                $stmt->execute();
                
                if($stmt->rowCount()>0)
                {
                    $result=$stmt->fetchAll(PDO::FETCH_ASSOC);
                    $msg=$this->generateResponse(FALSE,json_encode($result),TRUE);
                    return $msg;                
                }
                else
                {
                    $msg=$this->generateResponse(TRUE,$this->messages["error"],FALSE);
                    return $msg;                                
                }
            }
            catch(PDOException $e){
                $msg=$this->generateResponse(TRUE,$e->getMessage(),FALSE);
                return $msg;                 
            }
        }

        public function newUser()
        {
            $query = sprintf("INSERT INTO %s (email,pass,resetQ,resetAns) VALUES ('%s','%s','%s','%s')",$this->table,$this->email,$this->pass,$this->resetQ,$this->resetAns);            
            //return $query;
            try
            {
                $config = array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false);				
                $con = new PDO("mysql:host=".$this->dbArr['dbserver'].";dbname=".$this->dbArr['dbname']."", $this->dbArr['dbuser'], $this->dbArr['dbpass'], $config);	
                $stmt=$con->prepare($query);
                $stmt->execute();
                if($stmt->rowCount()>0)
                {                    
                    $msg=$this->generateResponse(FALSE,$this->messages["insupd"],FALSE);
                    return $msg; 
                }
                else
                {
                    $msg=$this->generateResponse(TRUE,$stmt->errorInfo(),FALSE);
                    return $msg;            
                }
            }
            catch(PDOException $e){
                $msg=$this->generateResponse(TRUE,$e->getMessage(),FALSE);
                return $msg; 
            }            
        }

        public function saveUser()
        {            
            $query = sprintf("UPDATE %s SET email='%s',pass='%s',resetQ='%s',resetAns='%s' WHERE email = '%s'",$this->table,$this->email,$this->pass,$this->resetQ,$this->resetAns,$this->email);            
            //return $query;
            try
            {
                $config = array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false);				
                $con = new PDO("mysql:host=".$this->dbArr['dbserver'].";dbname=".$this->dbArr['dbname']."", $this->dbArr['dbuser'], $this->dbArr['dbpass'], $config);	
                $stmt=$con->prepare($query);
                $stmt->execute();
                if($stmt->rowCount()>0)
                {
                    $msg=$this->generateResponse(FALSE,$this->messages["insupd"],FALSE);
                    return $msg;
                }
                else
                {
                    $msg=$this->generateResponse(TRUE,$stmt->errorInfo(),FALSE);
                    return $msg; 
                }
            }
            catch(PDOException $e){
                $msg=$this->generateResponse(TRUE,$e->getMessage(),FALSE);
                return $msg; 
            }            
        }

        public function deleteUser()
        {
            $query=sprintf("DELETE FROM %s WHERE userid=%d",$this->table,$this->userid);
            //return $query;
            try
            {
                $config = array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false);				
                $con = new PDO("mysql:host=".$this->dbArr['dbserver'].";dbname=".$this->dbArr['dbname']."", $this->dbArr['dbuser'], $this->dbArr['dbpass'], $config);	
                $stmt=$con->prepare($query);
                $stmt->execute();
                if($stmt->rowCount()>0)
                {                    
                    $msg=$this->generateResponse(FALSE,$this->messages["del"],FALSE);
                    return $msg;
                }
                else
                {
                    $msg=$this->generateResponse(TRUE,$stmt->errorInfo(),FALSE);
                    return $msg;
                }
            }
            catch(PDOException $e){
                $msg=$this->generateResponse(TRUE,$e->getMessage(),FALSE);
                return $msg;
            } 
        }        
    }
?>