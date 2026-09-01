<?php

class Login extends SmartestSystemApplication{
	
	protected $_new_user;
	
	/* function startPage(){
		$this->setTitle("Start Page");
	}
	
	function preferences(){
	
		$sql = "SELECT * FROM User, UserGroup WHERE User.user_group = UserGroup.usergroup_id";    
		$users = $this->manager->database->rawQuery($sql);
		$sql = "SELECT * FROM UserGroup";    
		$groups = $this->manager->database->rawQuery($sql);

		if(count($groups) && count($users)){
			return ( array("users" => $users, "groups" => $groups) );
		}else{
			return false;
		}    
	} */
	
	public function loginScreen(){
		
        $this->startSession();
        
		if($this->getUser() && $this->getUser()->isAuthenticated() && $this->_auth->getSystemUserIsLoggedIn()){
		    $this->redirect('/smartest');
		}
        
        $colours = array('blue', 'magenta', 'orange', 'red');
        $colour_index = mt_rand (0, 3);
        $this->send($colour_index, 'colour_index');
        $this->send($colours[$colour_index], 'start_colour');
		
		/*if(isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING']) && $this->requestParameterIsSet('from')){
		    
		     $vars = array();
		    
		    foreach($get as $key => $refer_var){
		        if($key != 'from'){
		            $vars[$key] = strip_tags($refer_var);
		            $vars[$key] = preg_replace('/[^\w&\._\/-]/', '', $refer_var);
	            }
		    } 
		    
		}else{
		    // $this->send('', 'refer');
		} */
		
	}
	
	public function doAuth(){
		
        $this->startSession();
        
        $username = $this->getRequestParameter('user');
        $password = $this->getRequestParameter('passwd');
        $service = 'SMARTEST';
        
        if(!is_scalar($username) || !is_scalar($password)){
            $this->redirect("/smartest/login#badauth");
        }
        
        $username = trim((string) $username);
        $password = (string) $password;
        
        if(strlen($username) < 1 || strlen($username) > 128 || strlen($password) > 1024 || preg_match('/[\x00-\x1F\x7F]/', $username)){
            $this->redirect("/smartest/login#badauth");
        }
		
		if($this->getUser() && $this->getUser()->isAuthenticated() && $this->_auth->getSystemUserIsLoggedIn()){
		    $this->redirect('/smartest');
		}
		
		if($user = $this->_auth->newLogin($username, $password, $service)){
		    
		    SmartestSession::set('user', $user);
		    
		    if($this->getUser()->getId()){
			    
			    $last_site_id = $this->getCookie('SMARTEST_LPID');
			    $allowed_site_ids = $this->getUser()->getAllowedSiteIds();
			    
			    SmartestLog::getInstance('site')->log("{$this->getUser()->__toString()} logged in.", SmartestLog::USER_ACTION);
			    
    	        if(is_numeric($last_site_id)){
    	            
    	            if(in_array($last_site_id, $allowed_site_ids)){
    	                
    	                if(strlen($this->getCookie('SMARTEST_RET'))){
    	                    
    	                    // $url = '/'.$this->getCookie('SMARTEST_RET');
    	                    $url = $this->getSafeReturnUrl($this->getCookie('SMARTEST_RET'));
                            $this->clearCookie('SMARTEST_RET');
    	                    
    	                    // user still has access to last edited site, so return to what they were last doing
    	                    $site = new SmartestSite;

            		        if($site->find($last_site_id)){

            			        SmartestSession::set('current_open_project', $site);
            			        $this->getUser()->reloadTokens();
                                $this->redirect($url);
        		        
            		        }else{
            		            
            		            // They have access to a site ID which doesn't exist
            		            
            		        }
        		        
    		            }else{
    		                
    		                $this->redirect("/smartest");
    		                
    		            }
        		        
    	            }else{
    	                // user no longer has access to that site
    	                $this->addUserMessageToNextRequest("Smartest could not return you to what you were last working on because you no longer have permission to work on that site.", SmartestUserMessage::ACCESS_DENIED);
    	                $this->redirect("/smartest");
    	            }
    	            
    	        }else{
    	            
    	            $this->redirect("/smartest");
    	            
    	        }
    	        
    	    }else{
    	        // User is not hydrated
    	        $this->redirect("/smartest");
    	    }
			
		}else{
			$this->redirect("/smartest/login#badauth");
		}
	}
	
	public function doLogOut(){
	    
        $this->clearCookie('SMARTEST_RET');
	    $this->clearCookie('SMARTEST_LPID');
        
        if($this->getUser() instanceof SmartestSystemUser){
            $this->getUser()->releaseItems();
            $this->getUser()->releasePages();
        }
        
		$this->_auth->logout();
		$this->redirect("/smartest/login#logout");
        
	}
	
	private function getSafeReturnUrl($url){
	    
	    if(!is_scalar($url)){
	        return '/smartest';
	    }
	    
	    $url = trim((string) $url);
	    
	    if(!strlen($url) || strlen($url) > 1024){
	        return '/smartest';
	    }
	    
	    if(preg_match('/[\x00-\x1F\x7F]/', $url) || preg_match('/^[a-z][a-z0-9+\.-]*:/i', $url) || substr($url, 0, 2) == '//' || strpos($url, '\\') !== false){
	        return '/smartest';
	    }
	    
	    if(substr($url, 0, 1) != '/'){
	        return '/smartest';
	    }
	    
	    return $url;
	    
	}
    
}
