<?php
/**
 * @Author: Noumaan Asif
 * HomeController.
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller
{
	
	/**
	 * Controller constructor
	 */
	 
	function __construct()
	{
		parent::__construct();
		$this->load->library('twitteroauth');
		// Loading twitter configuration.
		//$this->config->load('twitter');
		
		if($this->session->userdata('access_token') && $this->session->userdata('access_token_secret'))
		{
			// If user already logged in
			$this->connection = $this->twitteroauth->create($this->config->item('twitter_consumer_token'), $this->config->item('twitter_consumer_secret'), $this->session->userdata('access_token'),  $this->session->userdata('access_token_secret'));
		}
		elseif($this->session->userdata('request_token') && $this->session->userdata('request_token_secret'))
		{
			// If user in process of authentication
			$this->connection = $this->twitteroauth->create($this->config->item('twitter_consumer_token'), $this->config->item('twitter_consumer_secret'), $this->session->userdata('request_token'), $this->session->userdata('request_token_secret'));
		}
		else
		{
			// Unknown user
			$this->connection = $this->twitteroauth->create($this->config->item('twitter_consumer_token'), $this->config->item('twitter_consumer_secret'));
		}
	}
	 public function index(){
		 if($this->session->userdata('admin_login') == true){
			 
			 redirect(base_url()."admin/dashboard/");
		 }else if($this->session->userdata('logged_in') == true){
			 redirect(base_url()."home/app/dashboard/");
		 }else if($this->session->userdata('m_logged_in') == true){
			 redirect(base_url()."marketer/dashboard/");
		 }else{
			 $this->load->view("front/commons/head");
			 $this->load->view("front/login");
			 $this->load->view("front/commons/bottom");
		 }
		
	 }
	 
	 public function register($para=""){
		 if($this->session->userdata('logged_in') == true){
			 redirect(base_url()."home/app/dashboard/");
		 }
		 $this->load->library('form_validation');
		 if($para == "proceed"){
			$this->form_validation->set_rules('full_name', 'Name', 'required');
            $this->form_validation->set_rules('email', 'Email', 'required');
            $this->form_validation->set_rules('password', 'Password', 'required');
            if ($this->form_validation->run() == FALSE)
            {
				$error = validation_errors();
				echo json_encode(Array("status"=>"0","message"=>"Please check all the fields."));
            }
			else{ 
				if($this->input->post("is_manager")!=null && $this->input->post("is_manager")==="yes"){
					
					$code = random_string('alnum', 20);
					$data = Array(
						"name" => trim($this->input->post("full_name")),
						"email" => trim($this->input->post("email")),
						"password" => md5(trim($this->input->post("password"))),
						"v_email" => 0,
						"c_email" => $code,
						"forgot" => 0,
						"r_code" => null,
						"picture" => null,
					);
					$status = $this->user->register_user($data,"manager");
					//print_r($status);
					if($status["code"] !=200){
						
						echo json_encode(Array("status"=>"0","message"=>$status["message"]));
						return;
							  
							
					}else{ 
						echo json_encode(Array("status"=>"1",'redirect_uri'=>base_url().'home/login',"message"=>'You registration with Dance Influencers was successful. Your privacy is very important to us, hence this extra step. Check your email or your spam to continue'));
						return;
						
					}
				}else{
					$code = random_string('alnum', 20);
					$data = Array(
						"name" => trim($this->input->post("full_name")),
						"email" => trim($this->input->post("email")),
						"password" => md5(trim($this->input->post("password"))),
						"v_email" => 0,
						"c_email" => $code,
						"login_provider" => 'self',
						"forgot" => 0,
						"r_code" => null,
						"gender" => null,
					);
					
					$status = $this->user->register_user($data);
					//print_r($status);
					if($status["code"] !=200){
						
						echo json_encode(Array("status"=>"0","message"=>$status["message"]));
						return;
							
							
					}else{
						echo json_encode(Array("status"=>"1",'redirect_uri'=>base_url().'home/login',"message"=>'You registration with Dance Influencers was successful. Your privacy is very important to us, hence this extra step. Check your email or your spam to continue'));
						return;
						
					}
				}
			}
			 
		 }else{
			 $this->load->view("front/commons/head");
			 $this->load->view("front/index");
			 $this->load->view("front/commons/bottom"); 
		 }
	 }
	 public function login($para=""){
		 if($this->session->userdata('logged_in') == true || $this->session->userdata('logged_in_m')){
			 redirect(base_url()."home/app/dashboard/");
		 }
		 if($para == "proceed"){
			 $data = array(
				"email"=>$this->input->post("email"),
				"password"=> md5($this->input->post("password")),
			 );
			 $auth = null;
			 if($this->input->post("manager")!=null && $this->input->post("manager")==='yes'){
				 $auth = $this->user->auth_user($data,"manager");
			 }else{
				 $auth = $this->user->auth_user($data);
			 }
			 
			 
			 if($auth["status"]){
				 redirect(base_url() . 'home/app/dashboard/');
			 }else{
				  $this->load->view("front/commons/head");
				 $this->load->view("front/login",Array("status"=>$auth["message"],"e_code"=>$auth["e_code"]));
				 $this->load->view("front/commons/bottom"); 
			 }
			 
		 }else{
			 $this->load->view("front/commons/head");
			 $this->load->view("front/login");
			 $this->load->view("front/commons/bottom"); 
		 }
	 }
	 
	 public function user($para="",$para2="",$para3=""){
		 
		 if($para == "reporting"){
			 $this->user->check_LoginStatus();
			 $id = $this->session->userdata('uid');
			 $data = $this->user->getUserData($id);
			 if($para2=="update"){
				$file = $this->helper->upload_file("file");
				if($file){
					$agr = Array(
						"campaign"=>$this->input->post("campaign"),
						"file"=>$this->helper->script_uri()."uploads/".$file
					);
					$this->db->insert("reports",$agr);
					$this->session->set_flashdata("msg","Agreement has been submitted successfully.");
					redirect(base_url()."home/user/reporting/");
				}else{
					$this->session->set_flashdata("msg","There was an error in uploading the file.");
					redirect(base_url()."user/reporting/");
				}
					
			}else{
				$camps = $this->db->where("inf_id",$this->session->userdata("uid"))->get("campaigns")->result_array();
				 $this->load->view("front/commons/head");
				 $this->load->view("front/reporting",Array("campaigns"=>$camps,"user"=>$data));
				 $this->load->view("front/commons/bottom_dashboard");
			}
		 }
		 if($para == "recover" && $para2=="proceed"){
			 $email = $this->input->post("rec_email");
			 $status = false;
			 //Varify that user with this email exists
			 if($this->input->post("is_manager") && $this->input->post("is_manager")==="yes"){
				 $status = $this->user->pwd_recover($email,"manager");
				 $this->load->view("front/commons/head");
				 $this->load->view("front/recover",$status);
				 $this->load->view("front/commons/bottom"); 
			 }else{
				 $status = $this->user->pwd_recover($email);
				 $this->load->view("front/commons/head");
				 $this->load->view("front/recover",$status);
				 $this->load->view("front/commons/bottom"); 
			 }
			 
			 
			 
		 }else if($para == "recovery"){
			 $rdata = Array();
			 if($this->input->get("spart") && $this->input->get("spart")==="manager"){
				 $rdata["type"]="managers";
			 }else{
				 $rdata["type"]=null;
			 }
			 $this->load->view("front/commons/head");
			 $this->load->view("front/recover",$rdata);
			 $this->load->view("front/commons/bottom"); 
		 }
		 
		 
		 
		 
		 //Email Confirmation Code goes here.
		 if($para=="confirm_email" && $para2!="" && $para3 !=""){
			 $this->db->where('id', $para2);
			 $table = ($this->input->get("utype") !=null && $this->input->get("utype") ==='users') ?"users":"managers";
			 $query = $this->db->get($table);
			 if($query->num_rows()>0){
				 $data = $query->result_array()[0];
				 if($para3 == $data["c_email"]){
					$this->db->where('id', $para2);
					$this->db->set('v_email', 1);
					$this->db->update($table);
					
					$this->session->set_flashdata("success","Email Confirmed Successfully!");
					$this->load->view("front/commons/head");
					$this->load->view("front/email_confirm",["type"=>$table]);
					$this->load->view("front/commons/bottom");
				}else{
					$this->session->set_flashdata("success","Wrong Confirmation Code!");
					$this->load->view("front/commons/head");
					$this->load->view("front/email_confirm",["type"=>$table]);
					$this->load->view("front/commons/bottom");
				}
			 }else{
					$this->session->set_flashdata("success","User Profile Does not exists!");
					$this->load->view("front/commons/head");
					$this->load->view("front/email_confirm");
					$this->load->view("front/commons/bottom"); 
			 }
			 
		 }
	 }
	 
	 public function app($para="", $para2=""){
		 $this->user->check_LoginStatus();
		 if($this->session->userdata('logged_in_m')){
				 redirect(base_url()."home/manager/"); 
		 }else if($this->session->userdata('logged_in') == true){
			 if($para == "dashboard"){
				 
				 $id = $this->session->userdata('uid');
				 $data = $this->user->getUserData($id);
				 $this->load->view("front/commons/head");
				 $this->load->view("front/dashboard",$data);
				 $this->load->view("front/commons/bottom_dashboard"); 
				 
			 }else if($para=="profile" && $para2="settings"){
				 
				 $id = $this->session->userdata('uid');
				 $data = $this->user->getUserData($id);
				 $this->load->view("front/commons/head");
				 $this->load->view("front/profile_settings",$data);
				 $this->load->view("front/commons/bottom_dashboard");
				 
			 }else{
				 redirect(base_url()."home/app/dashboard/");
			 }
			 
			 
		}else{
			redirect(base_url()."home/login/");
		}
	 }

	public function socialLogup($media=""){
			if($this->session->userdata('logged_in') == true){
				redirect(base_url()."home/app/dashboard/");
			}
			$data = Array(
					"social_id" => $this->input->get("id"),
					"name" => $this->input->get("dname"),
					"email" => ($this->input->get("email")!=null)? $this->input->get("email"):$this->input->get("id")."@xceltalent.com",
					"password" => md5('12345*%$#@!CFKSHFS'),
					"v_email" => 1,
					"c_email" => md5('123'),
					"login_provider" => $media,
					"forgot" => 0,
					"r_code" => null,
					"gender" => null,
					"picture" => $this->input->get("imguri"),
				);
			$status = $this->user->socialAuth($data);
			//print_r($status);
			if($status["code"] == 200){
				redirect(base_url()."home/app/dashboard/");
			}else{
				$this->load->view("front/commons/head");
				$this->load->view("front/login",Array("status"=>$status["message"]));
				$this->load->view("front/commons/bottom");
			}				
		}
		
		
		
	public function logout(){
		$redirect_uri = "";
		if($this->session->userdata('admin_login') == true){
			$redirect_uri = "admin";
		 
		 }else if($this->session->userdata('logged_in') == true){
			 $redirect_uri="";
		 }else if($this->session->userdata('m_logged_in') == true){
			 $redirect_uri="marketer";
		 }else if($this->session->userdata('logged_in_m')){
			 $redirect_uri="home/manager";
		 }else{
			 $redirect_uri="";
		 }
		$this->session->sess_destroy();
		redirect(base_url().$redirect_uri);
		
		
		
	}
	public function settings($id="",$action="",$beats=""){
		$this->user->check_LoginStatus();
		if($id=="profile" && $action=="update"){
			$picture="";
			
			$uid = $this->session->userdata("uid");
			$udata = $this->user->getUserData($uid);
			$password= $udata["password"];
			$picture = $udata["picture"];
			if($beats==="photo"){
				
				if (!empty($_FILES['picture12']['name'])){
						$img = $this->input->post("picture");
						$img = str_replace('data:image/png;base64,', '', $img);
						$img = str_replace(' ', '+', $img);
						$decoded=base64_decode($img);
						$new_name = time() . uniqid() .".png";
						if(file_put_contents("./uploads/" . $new_name,$decoded)){
							$picture =  $this->helper->script_uri()."uploads/" . $new_name;
						}
						
						//echo $picture;
				}
			}else{
				if(trim($this->input->post("password"))!==""){
					$password = md5($this->input->post("password"));
				}
			}
			
			$data = Array(
					"password" =>$password,
					"picture" => $picture
				);
				
				$this->db->where(Array("id"=>$uid));
				$this->db->update("users",$data);
				
				redirect(base_url()."home/app/profile/settings/");
			
		}
		if($id !== "" && $id !=="profile" && $action=="update"){
			$uid = $this->session->userdata("uid");
			
				
			$pricing = Array(
					"fb_price_b" =>$this->input->post("fb_price_b"),
					"fb_price_m" =>$this->input->post("fb_price_m"),
					"twt_price_b" =>$this->input->post("twt_price_b"),
					"twt_price_m" =>$this->input->post("twt_price_m"),
					"ins_price_p_b" =>$this->input->post("ins_price_p_b"),
					"ins_price_v_b" =>$this->input->post("ins_price_v_b"),
					"ins_price_v_m" =>$this->input->post("ins_price_v_m"),
					"ins_price_s_b" =>$this->input->post("ins_price_s_b"),
					"ins_price_s_m" =>$this->input->post("ins_price_s_m"),
					"yt_price_b" =>$this->input->post("yt_price_b"),
					"yt_price_m" =>$this->input->post("yt_price_m"),
					"musical_price_b" =>$this->input->post("musical_price_b"),
					"musical_price_m" =>$this->input->post("musical_price_m")
				);	
				$media_file="";
			if (!empty($_FILES['media_file']['name'])) {
					$media_file = $this->helper->upload_media_file();
			}else{
				$media_file = "";
				
			}
			//echo $media_file;
			$beatss =  (empty($this->input->post("beats")) != true) ? json_encode($this->input->post("beats")):null;
				
			$data = Array(
					"name" =>$this->input->post("name"),
					"location" =>$this->input->post("location"),
					"email" =>$this->input->post("email"),
					"timezone" =>$this->input->post("timezone"),
					"age" =>$this->input->post("age"),
					"gender" =>$this->input->post("gender"),
					"beats" =>$beatss,
					"city" =>$this->input->post("city"),
					"bio" =>$this->input->post("bio"),
					"website" =>$this->input->post("website"),
					"rss" =>$this->input->post("rss"),
					"country" =>$this->input->post("country"),
					"region" =>$this->input->post("region"),
					"ethnicity" =>$this->input->post("ethnicity"),
					"phone" =>$this->input->post("phone"),
					"media_file" =>$this->helper->script_uri()."uploads/".$media_file,
					"pricing" =>json_encode($pricing),
					
				);
			$this->db->where(Array("id"=>$uid));
			$this->db->update("users",$data);
			//print_r($data);
			//echo "I am here";
			redirect(base_url()."home/app/dashboard/");
			
		}
		
		
		
		
		
		
	}
	
	public function payment_settings($action=""){
		$this->user->check_LoginStatus();
		if($action==""){	
			 $id = $this->session->userdata('uid');
			 $data = $this->user->getUserData($id);
			 $this->load->view("front/commons/head");
			 $this->load->view("front/payment_settings",$data);
			 $this->load->view("front/commons/bottom_dashboard");
		}else if($action=="update"){
			$data = Array(
				"paypal"=>$this->input->post("paypal"),
				"venmo"=>$this->input->post("venmo"),
				"shipping_address"=>$this->input->post("shipping_address"),
			);
			$uid = $this->session->userdata("uid");
			$this->db->where("id",$uid);
			$this->db->update("users",$data);
			redirect(base_url() . "home/payment_settings/");
		}else if($action!=="" && $action!=="update"){ 
			redirect(base_url() . "home/payment_settings/");
		}
		
	}
	public function disconnect($media="",$user_id=null,$step=null){
		$this->user->check_LoginStatus();
		if($media != ""){
			$uid=null;
			if($user_id){
				$uid=$user_id;
			}else{
				$uid = $this->session->userdata("uid");
			}
			$this->db->where(Array("id"=>$uid));
			$this->db->update("users",Array("$media"=>0));
			if($media ==="facebook"){
				$data = Array(
						"fb_ID"=>"",
						"fb_data"=>null,
						"full_name"=>null,
						"page_id"=>"",
						"access_token"=>"",
						"followers"=>0,
						"likes"=>0,
						"engagement_rate"=>0,
						"last_check_date"=> (new \DateTime())->modify('-0 hours')->format('Y-m-d H:i:s')
					);
				$this->db->where("uid",$uid);
				$this->db->update("facebook",$data);
			}else if($media ==="twitter"){
				$data = Array(
						"twitter_id"=>"",
						"username"=>null,
						"followers"=>0,
						"following"=>0,
						"tweets"=>0,
						"likes"=>0,
						"engagement"=>0,
						"last_check_date"=> (new \DateTime())->modify('-0 hours')->format('Y-m-d H:i:s')
					);
				$this->db->where("uid",$uid);
				$this->db->update("twitter_users",$data);
			}else if($media ==="instagram"){
				$data = Array(
						"instagram_id"=>"",
						"username"=>null,
						"followers"=>0,
						"following"=>0,
						"average_engagement_rate"=>0,
						"last_check_date"=> (new \DateTime())->modify('-0 hours')->format('Y-m-d H:i:s')
					);
				$this->db->where("uid",$uid);
				$this->db->update("instagram_users",$data);
			}else if($media ==="youtube"){
				$data = Array(
						"youtube_id"=>"",
						"username"=>null,
						"subscribers"=>0,
						"views"=>0,
						"videos"=>0,
						"engagement"=>0,
						"last_check_date"=> (new \DateTime())->modify('-0 hours')->format('Y-m-d H:i:s')
					);
				$this->db->where("uid",$uid);
				$this->db->update("youtube_users",$data);
			}
			
			
			if(!$user_id){
				redirect(base_url()."home/app/dashboard/");
			}else{
				if($step){
					redirect(base_url()."home/manager/connectSocial/".$user_id);
				}else{
					redirect(base_url()."home/manager/edit/".$user_id);
				}
			}
		}else{
			redirect(base_url()."home/app/dashboard/");
		}
	}
	
	public function campaigns(){
		 //$id = $this->session->userdata('uid');
		 //$data = $this->user->getUserData($id);
		 redirect(base_url());
		 $this->load->view("front/commons/head");
		 $this->load->view("front/campaigns");
		 $this->load->view("front/commons/bottom_dashboard");
	}
	
	public function viewprofile($key="",$uid=""){
		if($key=="xcxtMXCSS2344DFA234c8dcccsd"){
			if($this->session->userdata('admin_login') != null && $this->session->userdata('admin_login')!=false){
				echo "You are admin and you can view the profiles when logged In";	
			}else{
				redirect(base_url()."admin/");
			}
		}else{
			if($key !="" && $this->session->userdata("mar_key")==$key && $this->session->userdata('mar_sess')==true){
				echo "Welcome you are eligible to visit pages now.";
			}	
		}
		
		if($key=="pass" && $uid !=""){
			$user = $this->user->getUserData($uid);
			$stats = $this->user->get_user_stats($uid);
			
			$data = Array(
					"user"=>$user,
					"stats"=>$stats
					);
			$this->load->view("front/commons/head");
			$this->load->view("marketer/profile",$data);
			$this->load->view("front/commons/bottom_dashboard",$data);
		}
	}
	
	
	public function err404(){
		$this->load->view("front/commons/head");
		$this->load->view("404");
		$this->load->view("front/commons/bottom_dashboard");
	}
	
	
	public function resetPass($para1="",$para2=""){
		
		if($para1 != "" && $para2 != ""){
			$table = $this->input->get("type");
			$uid = $para1;
			$key = $para2;
			
			$data = array("id"=>$uid,"r_code"=>$key);
			$pos = array(
					"forgot"=>1,
					"r_code"=>$key,
					"id"=>$uid
			);
			$this->db->where($pos);
			if($this->db->get($table)->num_rows() == 0){
				$data['code']=2013;
				$data["message"] = "The link is invalid or expired";
				$data["expired"] = true;
				$data["type"] = $table;
			}
			$data["type"]=$table;
			$this->load->view("front/commons/head");
			$this->load->view("front/reset", $data);
			$this->load->view("front/commons/bottom"); 
		}else{
			$table = $this->input->post("type");
			$uid = $this->input->post("usid");
			$key = $this->input->post("key");
			$data = array("id"=>$uid,"r_code"=>$key, "code"=>201,"message"=>"Passwords does not match!","type"=>$table);
			$pass = $this->input->post("pass_n_c");
			$passc = $this->input->post("pass_n");
			if($pass != $passc){
				$this->load->view("front/commons/head");
				$this->load->view("front/reset", $data);
				$this->load->view("front/commons/bottom");
			}else{
				$pos = array(
					"forgot"=>1,
					"r_code"=>$key,
					"id"=>$uid
				);
				$this->db->where($pos);
				$this->db->update($table,array(
										"forgot"=>0,
										"r_code"=>NULL,
										"password"=>md5($pass)
									));
				$this->load->view("front/commons/head");
				$this->load->view("front/reset", Array("code"=>200,"message"=>"Password updated successfully!","type"=>$table));
				$this->load->view("front/commons/bottom");
			}
		}
	}
	
	public function updateFBPage($page_id=""){
		$user_id=null;
		if($this->input->get('user')!=null && $this->input->get('user')!=-1){
			$user_id = $this->input->get('user');
		}else{
			$user_id = $this->session->userdata("uid");
		}
		$this->db->where("uid",$user_id);
		$dxb2 = $this->db->get("facebook");  
		$dxb = $dxb2->num_rows();
		$source_account = null;
		if($dxb){
			$source_account = $dxb2->result_array()[0];
		}
		$date = (new \DateTime())->format('Y-m-d H:i:s');
		if(!$source_account || ($source_account && (new \DateTime())->modify('-24 hours') > (new \DateTime($source_account["last_check_date"]))) || $page_id != $source_account["page_id"]) {
			$uid = $user_id;
			$this->db->where(Array("uid"=>$uid));
			$fbdata = $this->db->get("facebook")->result_array()[0];
			$udata = json_decode($fbdata["fb_data"]);
			$accessToken = "";
			$pname = "";
			
			foreach($udata->accounts->data as $page){
				if($page->id == $page_id){
					$accessToken = $page->access_token;
					$pname = $page->name;
					break;
				}
				
			}
			
			
		try{	
			
			$fans = json_decode(file_get_contents("https://graph.facebook.com/v3.2/".$page_id."/insights?access_token=". $accessToken. "&metric=page_fans"));
			$fans2 = json_decode(file_get_contents("https://graph.facebook.com/v3.2/".$page_id."?access_token=". $accessToken. "&fields=fan_count"));
			$desc = json_decode(file_get_contents("https://graph.facebook.com/v3.2/".$page_id."?access_token=". $accessToken. "&fields=about"));
			$page_posts_impressions = json_decode(file_get_contents("https://graph.facebook.com/v3.2/".$page_id."/insights?access_token=". $accessToken. "&metric=page_posts_impressions"));
			$page_post_engagements = json_decode(file_get_contents("https://graph.facebook.com/v3.2/".$page_id."/insights?access_token=". $accessToken. "&metric=page_post_engagements"));
			$page_fans_gender_age = json_decode(file_get_contents("https://graph.facebook.com/v3.2/".$page_id."/insights?access_token=". $accessToken. "&metric=page_fans_gender_age"));
			$page_fans_country = json_decode(file_get_contents("https://graph.facebook.com/v3.2/".$page_id."/insights?access_token=". $accessToken. "&metric=page_fans_country"));
			//print_r($desc);
			$descripton="";
			if($fans->data[0]->values[0]->value >10){
				if(isset($desc->about)){
					$descripton = $desc->about;
				}
			$gendersfb = Array(
					"male"=>0,
					"female"=>0,
					"other"=>0,
					"total"=>0
				);
			$age_genderfb = (array)$page_fans_gender_age->data[0]->values[0]->value;
			$mArr = Array();
			$fArr = Array();
			$uArr = Array();
			foreach ($age_genderfb as $key => $value){
				if(strpos($key, 'M') !== false){
					$gendersfb["male"] += $value;
					$mArr[$key] = $value;
				}
				if(strpos($key, 'F') !== false){
					$gendersfb["female"] += $value;
					$fArr[$key] = $value;
				}
				if(strpos($key, 'U') !== false){
					$gendersfb["other"] += $value;
					$fArr[$key] = $value;
				}
				$gendersfb["total"] +=$value;
			}
		
			$engagement_rate = 0.0;
			if($page_posts_impressions->data[0]->values[1]->value >0){
				$engagement_rate = ($page_post_engagements->data[0]->values[1]->value / $page_posts_impressions->data[0]->values[1]->value) * 100;
			}
			
			$list = (array)$page_fans_country->data[0]->values[0]->value;
			asort($list);
			$arr = array_reverse($list);
			$keys = array_keys($arr);
			$countries1 = Array(
					"c1"=>$keys[0],
					"t_c1"=>$arr[$keys[0]],
					"c2"=>$keys[1],
					"t_c2"=>$arr[$keys[1]],
					"c3"=>$keys[2],
					"t_c3"=>$arr[$keys[2]],
					"c4"=>$keys[3],
					"t_c4"=>$arr[$keys[3]],
					"c5"=>$keys[4],
					"t_c5"=>$arr[$keys[4]],
					"total"=>array_sum($arr)
					
				);
				
				
				$data = Array(
					"full_name"=>$pname,
					"page_id"=>$page_id,
					"description"=>$descripton,
					"access_token"=>$accessToken,
					"likes"=>$fans->data[0]->values[0]->value,
					"followers"=>$fans->data[0]->values[1]->value,
					"engagement_rate"=>$engagement_rate,
					"genders"=>json_encode($gendersfb),
					"ages"=>json_encode($age_genderfb),
					"countries"=> json_encode($countries1),
					"added_date"=> $date,
					"last_check_date"=> $date
					);
					if($data["followers"] !=null && $data["followers"] > 10){
						$this->db->where("uid",$uid);
						$this->db->update("facebook",$data);
						echo json_encode(Array("update"=>true,"message"=>"Account Updated successfully !"));							
					}else{
						echo json_encode(Array("update"=>false,"message"=>"We do not track Facebook pages with followers less than 10."));
					}
				
				}else{
					echo json_encode(Array("update"=>false,"message"=>"We do not track Facebook pages with followers less than 10."));	
					return;
				}
			}catch(Exception $error){
				echo json_encode(Array("update"=>false,"message"=>"Something went wrong!"));
				return;
			}
		}else{
			echo json_encode(Array("update"=>true,"message"=>"Account Updated successfully!"));
		}
		
		
		
		
		$this->db->where(['page_id' => $page_id]);
		$source_account = $this->db->get('facebook')->result_array()[0];
		//$date = (new \DateTime())->modify('+24 hours')->format('Y-m-d H:i:s');
		
		$this->db->where("facebook_user_id='".$source_account["id"]."' AND DATEDIFF('$date', `date`) = 0");
		
		$log_obj = $this->db->get("facebook_logs");
		$log = $log_obj->num_rows();
		$log_data = null;
		
			


            if($log) {
				
				$log_data = $log_obj->result_array()[0];
				$this->db->where(['id' => $log_data["id"]]);
               $this->db->update(
                    'facebook_logs',
                    [
                        'page_id' => $source_account["page_id"],
                        'followers' => $source_account["followers"],
                        'likes' => $source_account["likes"],
                        'engagement_rate' => $source_account["engagement_rate"],
                        'date' => $date
                    ]
                );
            } else {
                $this->db->insert(
                    'facebook_logs',
                    [
                        'facebook_user_id' => $source_account["id"],
						'page_id' => $source_account["page_id"],
                        'followers' => $source_account["followers"],
                        'likes' => $source_account["likes"],
                        'engagement_rate' => $source_account["engagement_rate"],
                        'date' => $date
                    ]
                );  
            }
		
		
		
		
		
	}
	
	public function deliverables($para2=""){
		$this->user->check_LoginStatus();
		$id = $this->session->userdata('uid');
			 $data = $this->user->getUserData($id);
			 if($para2=="update"){
				$file = $this->helper->upload_file("file");
				if($file){
					$agr = Array(
						"campaign"=>$this->input->post("campaign"),
						"file"=>$this->helper->script_uri()."uploads/".$file
					);
					$this->db->insert("deliverables",$agr);
					$this->session->set_flashdata("msg","Agreement has been submitted successfully.");
					redirect(base_url()."home/deliverables/");
				}else{
					$this->session->set_flashdata("msg","There was an error in uploading the file.");
					redirect(base_url()."home/deliverables/");
				}
					
			}else{
				 $user = $this->session->userdata("uid");
				 $deliverables = $this->db->select("deliverables.*, campaigns.title as c_title")->from("campaigns")->join('deliverables', 'deliverables.campaign = campaigns.id ')->where("campaigns.inf_id  like '%\"$user\"%' AND campaigns.status = 'active' AND deliverables.sent_by='admin' AND deliverables.sent_to='influencer' AND sent_to_ID=$user")->order_by("deliverables.id desc")->get()->result_array();
				 $this->load->view("front/commons/head");
				 $this->load->view("front/list_deliverables",Array("deliverables"=>$deliverables, "user"=>$data));
				 $this->load->view("front/commons/bottom_dashboard");
			}
	}
	
	public function agreements($para="",$action=""){
		$this->user->check_LoginStatus();
		$id = $this->session->userdata('uid');
			 $data = $this->user->getUserData($id);
			 if($para=="send" && $action=="update"){
				$file = $this->helper->upload_file("file");
				if($file){
					$agr = Array(
						"campaign"=>$this->input->post("campaign"),
						"file"=>$this->helper->script_uri()."uploads/".$file,
						"sent_by"=>"influencer",
						"sent_to"=>"admin",
						"sender_id"=>$this->session->userdata("uid")
					);
					$this->db->insert("agreements",$agr);
					$this->session->set_flashdata("msg","Agreement has been submitted successfully.");
					redirect(base_url()."home/agreements/");
				}else{
					$this->session->set_flashdata("msg","There was an error in uploading the file.");
					redirect(base_url()."home/agreements/");
				}
					
			}else if($para=="send" && $action==""){
				 $camps = $this->db->where("inf_id like '%\"" . $this->session->userdata("uid") . "\"%'")->get("campaigns")->result_array();
				 $this->load->view("front/commons/head");
				 $this->load->view("front/agreements",Array("campaigns"=>$camps,"user"=>$data));
				 $this->load->view("front/commons/bottom_dashboard");
			}else{
				 $user = $this->session->userdata("uid");
				$agreements = $this->db->select("agreements.*, campaigns.title as c_title")->from("campaigns")->join('agreements', 'agreements.campaign = campaigns.id ')->where("campaigns.inf_id like '%\"$user\"%' AND campaigns.status = 'active' AND agreements.sent_by='admin' AND agreements.sent_to='influencer' AND sent_to_ID=$user")->order_by("agreements.id desc")->get()->result_array();
				 $this->load->view("front/commons/head");
				 $this->load->view("front/list_agreements",Array("agreements"=>$agreements,"user"=>$data));
				 $this->load->view("front/commons/bottom_dashboard");
			}
	}
	
	
	public function reports($para="",$action=""){
		$this->user->check_LoginStatus();
		$id = $this->session->userdata('uid');
			 $data = $this->user->getUserData($id);
			 if($para=="send" && $action=="update"){
				$file = $this->helper->upload_file("file");
				if($file){
					$agr = Array(
						"campaign"=>$this->input->post("campaign"),
						"file"=>$this->helper->script_uri()."uploads/".$file,
						"sent_by"=>"influencer",
						"sent_to"=>"admin",
						"sender_id"=>$this->session->userdata("uid")
					);
					$this->db->insert("reports",$agr);
					$this->session->set_flashdata("msg","Report has been submitted successfully.");
					redirect(base_url()."home/reports/send/");
				}else{
					$this->session->set_flashdata("msg","There was an error in uploading the file.");
					redirect(base_url()."home/reports/send/");
				}
					
			}else if($para=="send" && $action==""){
				 $camps = $this->db->where("inf_id like '%\"" . $this->session->userdata("uid") . "\"%'")->get("campaigns")->result_array();
				 $this->load->view("front/commons/head");
				 $this->load->view("front/reports",Array("campaigns"=>$camps,"user"=>$data));
				 $this->load->view("front/commons/bottom_dashboard");
			}else{
				 $user = $this->session->userdata("uid");
				$reports = $this->db->select("reports.*, campaigns.title as c_title")->from("campaigns")->join('reports', 'reports.campaign = campaigns.id ')->where("campaigns.inf_id  like '%\"$user\"%' campaigns.status = 'active' AND reports.sent_by='admin' AND reports.sent_to='influencer' AND reports.sent_to_ID=$user")->order_by("reports.id desc")->get()->result_array();
				 $this->load->view("front/commons/head");
				 $this->load->view("front/list_reports",Array("reports"=>$reports,"user"=>$data));
				 $this->load->view("front/commons/bottom_dashboard");
			}
	}
	
	public function updateAgreementStatus($id=""){
		$this->user->check_LoginStatus();
		$data = Array("viewed"=>1);
		$this->db->where("id",$id);
		$this->db->update("agreements",$data);
		echo 1;
	}
	
	public function updateMusicStatus($id=""){
		$this->user->check_LoginStatus();
		$data = Array("viewed"=>1);
		$this->db->where("id",$id);
		$this->db->update("music",$data);
		echo 1;
	}
	
	public function music($para="",$action=""){
		$this->user->check_LoginStatus();
			 $id = $this->session->userdata('uid');
			 $data = $this->user->getUserData($id);
			 if($para=="send" && $action=="update"){
				$file = $this->helper->upload_file("file");
				if($file){
					$agr = Array(
						"campaign"=>$this->input->post("campaign"),
						"file"=>$this->helper->script_uri()."uploads/".$file,
						"sent_by"=>"influencer",
						"sent_to"=>"admin",
						"sender_id"=>$this->session->userdata("uid")
					);
					$this->db->insert("music",$agr);
					$this->session->set_flashdata("msg","Music has been submitted successfully.");
					redirect(base_url()."home/music/");
				}else{
					$this->session->set_flashdata("msg","There was an error in uploading the file.");
					redirect(base_url()."home/music/");
				}
					
			}else if($para=="send" && $action==""){
				 $camps = $this->db->where("inf_id",$this->session->userdata("uid"))->get("campaigns")->result_array();
				 $this->load->view("front/commons/head");
				 $this->load->view("front/music",Array("campaigns"=>$camps,"user"=>$data));
				 $this->load->view("front/commons/bottom_dashboard");
			}else{
				 $user = $this->session->userdata("uid");
				$music = $this->db->select("music.*, campaigns.title as c_title")->from("campaigns")->join('music', 'music.campaign = campaigns.id ')->where("campaigns.inf_id  like '%\"$user\"%' AND campaigns.status = 'active' AND music.sent_by='admin' AND music.sent_to='influencer' AND sent_to_ID=$user")->order_by("music.id desc")->get()->result_array();
				 $this->load->view("front/commons/head");
				 $this->load->view("front/list_music",Array("music"=>$music,"user"=>$data));
				 $this->load->view("front/commons/bottom_dashboard");
			}
	}
	


	public function connect_insta(){
		$this->user->check_LoginStatus();
		$user = $this->input->get("value");
		$instagram = $this->instascrap->getScrapper();
		$instagram->setUserAgent($this->InstagramHelper->get_random_user_agent());
		
		try {
        $source_account_data = $instagram->getAccount($user);
    } catch (Exception $error) {
        echo json_encode(Array("status"=>false,"message"=>$error->getMessage()));
		return;
    }
	
	$source_account_new = new StdClass();
    $source_account_new->instagram_id = $source_account_data->getId();
    $source_account_new->username = $source_account_data->getUsername();
    $source_account_new->full_name = $source_account_data->getFullName();
    $source_account_new->description = $source_account_data->getBiography();
    $source_account_new->website = $source_account_data->getExternalUrl();
    $source_account_new->followers = $source_account_data->getFollowedByCount();
    $source_account_new->following = $source_account_data->getFollowsCount();
    $source_account_new->uploads = $source_account_data->getMediaCount();
    $source_account_new->profile_picture_url = $source_account_data->getProfilePicUrl();
    $source_account_new->is_private = (int) $source_account_data->isPrivate();
    $source_account_new->is_verified = (int) $source_account_data->isVerified();
    $date = (new \DateTime())->format('Y-m-d H:i:s');
	if($source_account_new->followers <1){
		echo json_encode(Array("status"=>false,"message"=>"We do not track Instagram accounts with less than 1 followers!"));
		return;
	}
	if($source_account_data->isPrivate()){
		echo json_encode(Array("status"=>false,"message"=>"Account you provided is a private account. Kindly make it public and then try again."));
		return;
	}
	if($source_account_new->is_private) {
        $source_account_new->average_engagement_rate = '';
        $details = '';
    }
		
    else {
        $media_response = $instagram->getPaginateMedias($user);


        /* Get extra details from last media */
        $likes_array = [];
        $comments_array = [];
        $engagement_rate_array = [];
        $hashtags_array = [];
        $mentions_array = [];
        $top_posts_array = [];
        $details = [];

		$mediass = Array();
		
        /* Go over each recent media post */
        foreach ($media_response['medias'] as $media) {
			$mediass[] = $media->getLink();
            $likes_array[$media->getShortCode()] = $media->getLikesCount();
            $comments_array[$media->getShortCode()] = $media->getCommentsCount();
            $engagement_rate_array[$media->getShortCode()] = number_format(($media->getLikesCount() + $media->getCommentsCount()) / $source_account_new->followers * 100, 2);

            $hashtags = InstagramHelper::get_hashtags($media->getCaption());

            foreach ($hashtags as $hashtag) {
                if (!isset($hashtags_array[$hashtag])) {
                    $hashtags_array[$hashtag] = 1;
                } else {
                    $hashtags_array[$hashtag]++;
                }
            }

            $mentions = InstagramHelper::get_mentions($media->getCaption());

            foreach ($mentions as $mention) {
                if (!isset($mentions_array[$mention])) {
                    $mentions_array[$mention] = 1;
                } else {
                    $mentions_array[$mention]++;
                }
            }

            if (count($likes_array) >= 10) break;
        }

        /* Calculate needed details */
        $details['total_likes'] = array_sum($likes_array);
        $details['total_comments'] = array_sum($comments_array);
        $details['average_comments'] = number_format($details['total_comments'] / count($comments_array), 2);
        $details['average_likes'] = number_format($details['total_likes'] / count($likes_array), 2);
        $source_account_new->average_engagement_rate = number_format(array_sum($engagement_rate_array) / count($engagement_rate_array), 2);

        /* Do proper sorting */
        arsort($engagement_rate_array);
        arsort($hashtags_array);
        arsort($mentions_array);
        $top_posts_array = array_slice($engagement_rate_array, 0, 3);
        $top_hashtags_array = array_slice($hashtags_array, 0, 15);
        $top_mentions_array = array_slice($mentions_array, 0, 15);

        /* Get them all together */
        $details['top_hashtags'] = $top_hashtags_array;
        $details['top_mentions'] = $top_mentions_array;
        $details['top_posts'] = $top_posts_array;
        $details = json_encode($details);
		
		}
	
	  $uid=null;
	  if($this->input->get('user_id') !=null && $this->input->get('user_id')!=-1){
		  $uid = $this->input->get('user_id');
	  }else{
		$uid = $this->session->userdata("uid");
	  }
	  //Create array of whole data according to the table columns
	  
	  $data_arr = Array(
		"uid"=>$uid,
		"instagram_id" =>  $source_account_new->instagram_id,
		"username" => $source_account_new->username ,
		"full_name" => $source_account_new->full_name,
		"description" => $source_account_new->description ,
		"website" => $source_account_new->website,
		"followers" => $source_account_new->followers,
		"following" => $source_account_new->following ,
		"uploads" =>$source_account_new->uploads ,
		"average_engagement_rate" => $source_account_new->average_engagement_rate,
		"details" => $details,
		"profile_picture_url" => $source_account_new->profile_picture_url,
		"is_private" => $source_account_new->is_private,
		"is_verified" => $source_account_new->is_verified,
		"added_date" => $date,
		"last_check_date" =>$date,
		"links" => json_encode($mediass)
	  );
	  
	  $this->db->where("uid",$uid);
	  $old_acc_data = $this->db->get("instagram_users")->num_rows();
	  if(!$old_acc_data){
			$this->db->where("instagram_id",$source_account_new->instagram_id);
			$dxbs = $this->db->get("instagram_users")->num_rows();
			if($dxbs==0){
			  $this->db->insert("instagram_users",$data_arr);
			  $this->db->where("id",$uid);
			  $this->db->set("instagram",1);
			  $this->db->update("users");
			}
			else{
				echo json_encode(Array("status"=>false,"message"=>"Instagram account already in use. Please contact support for further assistance"));
				return;
			}
		  
	  }else{
		  $this->db->where("instagram_id",$source_account_new->instagram_id);
			$dxbs = $this->db->get("instagram_users")->num_rows();
			if($dxbs==0){
		  $this->db->where("uid",$uid);
		  $this->db->update("instagram_users",$data_arr);
		  $this->db->where("id",$uid);
		  $this->db->set("instagram",1);
		  $this->db->update("users");
			}else{
				echo json_encode(Array("status"=>false,"message"=>"Instagram account already in use. Please contact support for further assistance"));
				return;
			}
	  }
	  
	  
	  
	  
		$this->db->where(['username' => $user]);
		$source_account = $this->db->get('instagram_users')->result_array()[0];
		//$date = (new \DateTime())->modify('+24 hours')->format('Y-m-d H:i:s');
		
		$this->db->where("instagram_user_id='".$source_account["id"]."' AND DATEDIFF('$date', `date`) = 0");
		
		$log_obj = $this->db->get("instagram_logs");
		$log = $log_obj->num_rows();
		$log_data =null;


            if($log) {
				$log_data = $log_obj->result_array()[0];
				$this->db->where(['id' => $log_data["id"]]);
               $this->db->update(
                    'instagram_logs',
                    [
                        'username' => $source_account["username"],
                        'followers' => $source_account["followers"],
                        'following' => $source_account["following"],
                        'uploads' => $source_account["uploads"],
                        'average_engagement_rate' => $source_account["average_engagement_rate"],
                        'date' => $date
                    ]
                );
            } else {
                $this->db->insert(
                    'instagram_logs',
                    [
                        'instagram_user_id' => $source_account["id"],
						'username' => $source_account["username"],
                        'followers' => $source_account["followers"],
                        'following' => $source_account["following"],
                        'uploads' => $source_account["uploads"],
                        'average_engagement_rate' => $source_account["average_engagement_rate"],
                        'date' => $date
                    ]
                );  
            }
	  
	  
	  echo json_encode(Array("status"=>true,"message"=>"Instagram account connected successfully!"));
			return;
	}
	
	public function connect_yt(){
		$this->user->check_LoginStatus();
		$user =  $this->input->get("value");
		$user_id=null;
		  if($this->input->get('user_id') !=null && $this->input->get('user_id')!=-1){
			  $user_id = $this->input->get('user_id');
		  }else{
			$user_id = $this->session->userdata("uid");
		  }
		
		$cvvvv = $this->db->where("uid",$user_id)->get("youtube_users");
		$dxb = $cvvvv->num_rows();
		$dxb2 = $cvvvv;
		$source_account = null;
		if($dxb){
			$source_account = $cvvvv->result_array()[0];
		}
			
		$this->YouTube->setApiKey($this->config->item('youtube_app_id'));
		$source_account_data = $this->YouTube->get('channels', 'part=snippet,statistics&id='. $user)->items;
		$cursor="";
		$statistics = Array(
			"vcount"=>0,
			"viewCount"=>0,
			"likeCount"=>0,
			"dislikeCount"=>0,
			"favoriteCount"=>0,
			"commentCount"=>0
		
		);
		if(!is_array($source_account_data) || empty($source_account_data)) {
			echo json_encode(Array("status"=>0,"message"=>"The user was not found in the YouTube Database"));
			return;
		}

		if($source_account_data[0]->statistics->hiddenSubscriberCount) {
			echo json_encode(Array("status"=>0,"message"=>"We do not track Youtube accounts with hidden subscriber counts."));
			return;
		}

		$source_account_data = $source_account_data[0];

		/* Vars to be added & used */
		$source_account_new = new StdClass();
		$source_account_new->youtube_id = $source_account_data->id;
		$source_account_new->username = $user;
		$source_account_new->title = $source_account_data->snippet->title;
		$source_account_new->description = $source_account_data->snippet->description;
		$source_account_new->subscribers = $source_account_data->statistics->subscriberCount;
		$source_account_new->views = $source_account_data->statistics->viewCount;
		$source_account_new->videos = $source_account_data->statistics->videoCount;
		$source_account_new->profile_picture_url = $source_account_data->snippet->thumbnails->high->url ?? '';
		$date = (new \DateTime())->format('Y-m-d H:i:s');

		/* Try to insert it if the account doesn't exist */
		if(!$source_account) {
			
			/* We need to add the user to the database */
			
			$new_Data = Array(
				"uid"=>$user_id,
				"viewCount"=>$statistics["viewCount"],
				"likeCount"=>$statistics["likeCount"],
				"dislikeCount"=>$statistics["dislikeCount"],
				"favoriteCount"=>$statistics["favoriteCount"],
				"commentCount"=>$statistics["commentCount"],
				"youtube_id" =>$source_account_new->youtube_id,
				"username" => $source_account_new->username,
				"title" => $source_account_new->title,
				"description" => $source_account_new->description,
				"subscribers" => $source_account_new->subscribers,
				"views" => $source_account_new->views,
				"videos" => $source_account_new->videos,
				"engagement"=>$statistics["viewCount"] == 0 ? 0 : round((($statistics["likeCount"] - $statistics["dislikeCount"] + $statistics["favoriteCount"] + $statistics["commentCount"]) / ($statistics["viewCount"])) * 100,2),
				"profile_picture_url" => $source_account_new->profile_picture_url,
				"added_date" => $date,
				"last_check_date" => $date
			);
			$this->db->where("youtube_id",$source_account_new->youtube_id);
			$dxbs = $this->db->get("youtube_users")->num_rows();
			if($dxbs==0){
			
				$this->db->insert("youtube_users",$new_Data);
				
				$this->db->where("id",$user_id);
				$this->db->set("youtube",1);
				$this->db->update("users");
			}else{
				echo json_encode(Array("status"=>0,"message"=>"The requested youtube channel is already in use. Please contact support for further assistance"));
				return;
			}
		
		}
			$avgEng = json_decode(file_get_contents("https://www.geeksmash.com/wp-content/themes/burst-child/youtube_calc.php?username=$user&range=20"))->avgEngRate;
		/* If the user exist, update the data if past X hours */
		if($source_account && (new \DateTime())->modify('-0 hours') >= (new \DateTime($source_account["last_check_date"]))) {
			
			$new_Data = Array(
				"youtube_id" =>$source_account_new->youtube_id ,
				"username" => $source_account_new->username,
				"title" => $source_account_new->title,
				"description" => $source_account_new->description,
				"subscribers" => $source_account_new->subscribers,
				"views" => $source_account_new->views,
				"videos" => $source_account_new->videos,
				"profile_picture_url" => $source_account_new->profile_picture_url,
				"last_check_date" => $date,
				"viewCount"=>$statistics["viewCount"],
				"likeCount"=>$statistics["likeCount"],
				"dislikeCount"=>$statistics["dislikeCount"],
				"favoriteCount"=>$statistics["favoriteCount"],
				"commentCount"=>$statistics["commentCount"],
				"engagement"=>$avgEng
				
			);
			
			
			if($source_account_new->subscribers < 1) {
				
				echo json_encode(Array("status"=>0,"message"=>"We are sorry but we only add accounts to our database with 1 or more Subscribers.."));
				return;
		
			}

			if($source_account["youtube_id"] !== $source_account_new->youtube_id){
				$this->db->where("youtube_id",$source_account_new->youtube_id);
				$dxbs = $this->db->get("youtube_users")->num_rows();
				if($dxbs==0){
					$this->db->where("uid",$user_id);
					$this->db->update("youtube_users",$new_Data);
					$this->db->where("id",$user_id);
					$this->db->set("youtube",1);
					$this->db->update("users");
				}else{
					echo json_encode(Array("status"=>0,"message"=>"The requested youtube channel is already in use. Please contact support for further assistance"));
					return;
				}
			}	
		}
		
		
		
		
		$this->db->where(['username' => $user]);
		$source_account = $this->db->get('youtube_users')->result_array()[0];
		//$date = (new \DateTime())->modify('+24 hours')->format('Y-m-d H:i:s');
		
		$this->db->where("youtube_user_id='".$source_account["id"]."' AND DATEDIFF('$date', `date`) = 0");
		
		$log_obj = $this->db->get("youtube_logs");
		$log = $log_obj->num_rows();
		$log_data = null;


            if($log) {
				$log_data = $log_obj->result_array()[0];
				$this->db->where(['id' => $log_data["id"]]);
               $this->db->update(
                    'youtube_logs',
                    [
                        'subscribers' => $source_account["subscribers"],
                        'views' => $source_account["views"],
                        'videos' => $source_account["videos"],
                        'engagement' => $source_account["engagement"],
                        'viewCount' => $source_account["viewCount"],
                        'likeCount' => $source_account["likeCount"],
                        'dislikeCount' => $source_account["dislikeCount"],
                        'favoriteCount' => $source_account["favoriteCount"],
                        'commentCount' => $source_account["commentCount"],
                        'date' => $date
                    ]
                );
            } else {
                $this->db->insert(
                    'youtube_logs',
                    [
                        'youtube_user_id' => $source_account["id"],
                        'username' => $source_account["username"],
                        'subscribers' => $source_account["subscribers"],
                        'views' => $source_account["views"],
                        'videos' => $source_account["videos"],
                        'engagement' => $source_account["engagement"],
                        'viewCount' => $source_account["viewCount"],
                        'likeCount' => $source_account["likeCount"],
                        'dislikeCount' => $source_account["dislikeCount"],
                        'favoriteCount' => $source_account["favoriteCount"],
                        'commentCount' => $source_account["commentCount"],
                        'date' => $date
                    ]
                );  
            }
			
			
		
		
		echo json_encode(Array("status"=>1,"message"=>"Account added Successfully!"));
		
	}
	
	
	
	
	
	
	public function connect_twt(){
		$this->user->check_LoginStatus();
		$user =   $this->input->get("value");
		$user_id=null;
		  if($this->input->get('user_id') !=null && $this->input->get('user_id')!=-1){
			  $user_id = $this->input->get('user_id');
		  }else{
			$user_id = $this->session->userdata("uid");
		  }
		$this->load->model("TwitterHandler");
		echo $this->TwitterHandler->get_twitter_profile($user,$user_id);

        

	}
	
	
	public function connectFB($user_id=null){
		$this->facebook->destroy_session();
		if($user_id){
			$this->session->set_userdata('manager_auth',true);
			$this->session->set_userdata('m_auth_uid',$user_id);
		}else{
			$this->session->set_userdata('manager_auth',false);
			$this->session->set_userdata('m_auth_uid',NULL);
		}
		
		if($this->input->get('step')!=null){
			$this->session->set_userdata('step',true);
		}else{
			$this->session->set_userdata('step',false);
		}
		redirect($this->facebook->login_url());
	}
	
	
	
	public function manager($type="",$page="",$action=""){
		
		if($type==="" && $this->session->userdata('logged_in_m')){
			$id = $this->session->userdata('manager_id');
			$data["manager"] = $this->user->getUserData($id,"manager");
			$data["listing"] = false;
			$this->load->view("front/commons/head");
			$this->load->view("front/dashboard_manager",$data);
			$this->load->view("front/commons/bottom_dashboard");
		}else if($type==="login" && $page===""){
			if($this->session->userdata('logged_in') == true || $this->session->userdata('logged_in_m')){
				redirect(base_url()."home/manager");
			}  
		 	 $this->load->view("front/commons/head");
			 $this->load->view("front/login_manager");
			 $this->load->view("front/commons/bottom"); 
		 
		}else if($type==="login" && $page==="proceed"){
			$data = array(
				"email"=>$this->input->post("email"),
				"password"=> md5($this->input->post("password")),
			 );
			 $auth = null;
			 $auth = $this->user->auth_user($data,"manager");
			 if($auth["status"]){
				 redirect(base_url() . 'home/app/dashboard/');
			 }else{
				  $this->load->view("front/commons/head");
				 $this->load->view("front/login_manager",Array("status"=>$auth["message"],"e_code"=>$auth["e_code"]));
				 $this->load->view("front/commons/bottom"); 
			 }
			 
		}else if($type==="register" && $page===""){
			
			$this->load->view("front/commons/head");
			 $this->load->view("front/register_manager"); 
			 $this->load->view("front/commons/bottom"); 
			
		}else if($type==="register" && $page==="proceed"){
			$this->load->library('form_validation');
			$this->form_validation->set_rules('full_name', 'Name', 'required');
            $this->form_validation->set_rules('email', 'Email', 'required');
            $this->form_validation->set_rules('password', 'Password', 'required');
            if ($this->form_validation->run() == FALSE)
            {
				$error = validation_errors();
				echo json_encode(Array("status"=>"0","message"=>"Please check all the fields."));
            }
			else{ 	
					$code = random_string('alnum', 20);
					$data = Array(
						"name" => trim($this->input->post("full_name")),
						"email" => trim($this->input->post("email")),
						"password" => md5(trim($this->input->post("password"))),
						"v_email" => 0,
						"c_email" => $code,
						"forgot" => 0,
						"r_code" => null,
						"picture" => null,
					);
					$status = $this->user->register_user($data,"manager");
					//print_r($status);
					if($status["code"] !=200){
						
						echo json_encode(Array("status"=>"0","message"=>$status["message"]));
						return;
							  
							
					}else{ 
						echo json_encode(Array("status"=>"1",'redirect_uri'=>base_url().'home/manager',"message"=>'You registration with Dance Influencers was successful. Your privacy is very important to us, hence this extra step. Check your email or your spam to continue'));
						return;
						
					}
			}
				
		}else if($type==="connectSocial" && $this->session->userdata('logged_in_m') && is_numeric($page)){
			$id = $this->session->userdata('manager_id');
			$uid = $page;
			$data["manager"] = $this->user->getUserData($id,"manager");
			$this->db->where("id",$page);
				 $user = $this->db->get('users');
				 if($user->num_rows()==0){
					 $data["user"]=null;
				 }else{
					 $data["user"] = $user->result_array()[0]; 
					 $this->db->where('uid',$uid);
					 $data["instagram"] = $this->db->get('instagram_users')->result_array()[0];
					 $this->db->where('uid',$uid);
					 $data["youtube"] = $this->db->get('youtube_users')->result_array()[0];
					 $this->db->where('uid',$uid);
					 $data["facebook"] = $this->db->get('facebook')->result_array()[0];
					 $this->db->where('uid',$uid);  
					 $data["twitter"] = $this->db->get('twitter_users')->result_array()[0];
				 }
			$this->load->view("front/commons/head");
			$this->load->view("front/connectSocial_manager",$data);
			$this->load->view("front/commons/bottom_dashboard",$data);
		}else if($this->session->userdata('logged_in_m') && $type==="influencers" && $page===""){
			 //Fetch the users related to this specific manager
			 
			 $this->db->select('users.*, 
			instagram_users.followers as insta_followers, 
			instagram_users.average_engagement_rate, 
			twitter_users.followers as twt_followers, 
			twitter_users.engagement as twt_engagement, 
			youtube_users.subscribers as yt_subscribers, 
			youtube_users.engagement as yt_engagement, 
			facebook.followers as fb_followers, 
			facebook.engagement_rate as fb_engagement,
			facebook.page_id as fb_page_id,
			((twitter_users.followers)+(instagram_users.followers)+(youtube_users.subscribers)+(facebook.followers)) as audience,
			((twitter_users.engagement)+(instagram_users.average_engagement_rate)+(youtube_users.engagement)+(facebook.engagement_rate)) as t_engagement,')
			->from('users')
			->join('instagram_users', 'users.id = instagram_users.uid')
			->join('twitter_users', 'users.id = twitter_users.uid')
			->join('youtube_users', 'users.id = youtube_users.uid')
			->join('facebook', 'users.id = facebook.uid')->where("users.manager_id",$this->session->userdata('manager_id'))->order_by("audience DESC");
			$managed_influencers = $this->db->get()->result_array();
			
			
			 $data = Array();
			 $data["listing"] = true;
			 $id = $this->session->userdata('manager_id');
			 $data["manager"] = $this->user->getUserData($id,"manager");
			 $data["influencers"] = $managed_influencers;
			 $this->load->view("front/commons/head");
			 $this->load->view("front/dashboard_manager",$data);
			 $this->load->view("front/commons/bottom_dashboard");	
		 }else if($this->session->userdata('logged_in_m') && $type==="influencer" && $page==="add"){
			 
			 
			 $id = $this->session->userdata('manager_id');
			 $data["manager"] = $this->user->getUserData($id,"manager");
			 
			 $this->load->view("front/commons/head");
			 $this->load->view("front/manager_add_influencer",$data);
			 $this->load->view("front/commons/bottom_dashboard");
		 }else if($this->session->userdata('logged_in_m') && $type==="payment"){
			 
			 
			 $id = $this->session->userdata('manager_id');
			 $data = $this->user->getUserData($id,"manager");
			 
			 $this->load->view("front/commons/head");
			 $this->load->view("front/manager-payment",$data);
			 $this->load->view("front/commons/bottom_dashboard");
		 }else if($type==='add' && $this->session->userdata('logged_in_m')){
			 $media_file="";
			 
			 
			 $new_name = time().$_FILES['profile_pic']['name'];
			 $target_file = FCPATH . "uploads/" . $new_name;
			 
			if (!empty($_FILES['profile_pic']['name'])) {
					if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
						$media_file = $new_name;
					} else {
						$media_file = "";
					}
			}else{
				$media_file = "";
				
			}
			 $pricing = Array(
					"fb_price_b" =>$this->input->post("fb_price_b"),
					"fb_price_m" =>$this->input->post("fb_price_m"),
					"twt_price_b" =>$this->input->post("twt_price_b"),
					"twt_price_m" =>$this->input->post("twt_price_m"),
					"ins_price_p_b" =>$this->input->post("ins_price_p_b"),
					"ins_price_v_b" =>$this->input->post("ins_price_v_b"),
					"ins_price_v_m" =>$this->input->post("ins_price_v_m"),
					"ins_price_s_b" =>$this->input->post("ins_price_s_b"),
					"ins_price_s_m" =>$this->input->post("ins_price_s_m"),
					"yt_price_b" =>$this->input->post("yt_price_b"),
					"yt_price_m" =>$this->input->post("yt_price_m"),
					"musical_price_b" =>$this->input->post("musical_price_b"),
					"musical_price_m" =>$this->input->post("musical_price_m")
				);
			$beats =  (empty($this->input->post("beats")) != true) ? json_encode($this->input->post("beats")):null;
			$manager_id = $this->session->userdata('manager_id');
			 $data = Array(
					"name" =>$this->input->post("name"),
					"location" =>$this->input->post("location"),
					"email" =>$this->input->post("email"),
					"password" =>$this->input->post("password"),
					"timezone" =>$this->input->post("timezone"),
					"age" =>$this->input->post("age"),
					"gender" =>$this->input->post("gender"),
					"beats" =>$beats,
					"bio" =>$this->input->post("bio"),
					"website" =>$this->input->post("website"),
					"rss" =>$this->input->post("rss"),
					"country" =>$this->input->post("country"),
					"city" =>$this->input->post("city"),
					"region" =>$this->input->post("region"),
					"ethnicity" =>$this->input->post("ethnicity"),
					"phone" =>$this->input->post("phone"),
					"picture" =>$this->helper->script_uri()."uploads/".$media_file,
					"pricing" =>json_encode($pricing),
					"manager_id" =>$manager_id
					
				);
				$mail_u = $data["email"];
			
			
			if(false){
				echo  json_encode(Array("status"=>"0","message"=>"User with same email already exists!"));
				return;
			}else{
				
				$res = $this->db->insert('users',$data);
				$id = $this->db->insert_id();
				if($id){
					$this->db->insert("youtube_users",["uid"=>$id]);
					$this->db->insert("twitter_users",Array("uid"=>$id));
					$this->db->insert("instagram_users",Array("uid"=>$id));
					$this->db->insert("facebook",Array("uid"=>$id));
					echo  json_encode(Array("user_id"=>$id,"status"=>"1","message"=>"Influencer created successfully!"));
					return;
				}else{
					echo  json_encode(Array("status"=>"0","message"=>"Something went wrong. Contact support for further assistance."));
					return;
				}
				
			}
				
		 }else if($type==="edit" && $this->session->userdata('logged_in_m')){
			 $id = $this->session->userdata('manager_id');
			 $data["manager"] = $this->user->getUserData($id,"manager");
			 $uid = $page;
			 if($action==="update"){
					 $media_file=null;
					 	 $new_name = time().$_FILES['profile_pic']['name'];
						 $target_file = FCPATH . "uploads/" . $new_name;
						 
						if (!empty($_FILES['profile_pic']['name'])) {
								if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
									$media_file = $new_name;
								}
						}
					 
					 $pricing = Array(
							"fb_price_b" =>$this->input->post("fb_price_b"),
							"fb_price_m" =>$this->input->post("fb_price_m"),
							"twt_price_b" =>$this->input->post("twt_price_b"),
							"twt_price_m" =>$this->input->post("twt_price_m"),
							"ins_price_p_b" =>$this->input->post("ins_price_p_b"),
							"ins_price_v_b" =>$this->input->post("ins_price_v_b"),
							"ins_price_v_m" =>$this->input->post("ins_price_v_m"),
							"ins_price_s_b" =>$this->input->post("ins_price_s_b"),
							"ins_price_s_m" =>$this->input->post("ins_price_s_m"),
							"yt_price_b" =>$this->input->post("yt_price_b"),
							"yt_price_m" =>$this->input->post("yt_price_m"),
							"musical_price_b" =>$this->input->post("musical_price_b"),
							"musical_price_m" =>$this->input->post("musical_price_m")
						);
					 $beats =  (empty($this->input->post("beats")) != true) ? json_encode($this->input->post("beats")):null;
					 $data = Array(
							"name" =>$this->input->post("name"),
							"location" =>$this->input->post("location"),
							"email" =>$this->input->post("email"),
							"password" =>$this->input->post("password"),
							"timezone" =>$this->input->post("timezone"),
							"age" =>$this->input->post("age"),
							"gender" =>$this->input->post("gender"),
							"beats" =>$beats,
							"bio" =>$this->input->post("bio"),
							"website" =>$this->input->post("website"),
							"rss" =>$this->input->post("rss"),
							"country" =>$this->input->post("country"),
							"city" =>$this->input->post("city"),
							"region" =>$this->input->post("region"),
							"ethnicity" =>$this->input->post("ethnicity"),
							"phone" =>$this->input->post("phone"),
							"pricing" =>json_encode($pricing),
							
						);
						
						if($media_file){
							$data["picture"]=$this->helper->script_uri()."uploads/".$media_file;
						}
						
						$this->db->where('id',$uid);
						$this->db->update('users',$data);
						
						if($this->db->affected_rows()){
							echo  json_encode(Array("status"=>"1","message"=>"Influencer Updated successfully!"));
							return;
						}else{
							echo  json_encode(Array("status"=>"0","message"=>"Something went wrong. Contact support for further assistance."));
							return;
						}	 
			 }else{
			 if(is_numeric($uid)){
				 
				 $this->db->where("id",$uid);
				 $user = $this->db->get('users');
				 if($user->num_rows()==0){
					 $data["user"]=null;
				 }else{
					 $data["user"] = $user->result_array()[0];
					 $this->db->where('uid',$uid);
					 $data["instagram"] = $this->db->get('instagram_users')->result_array()[0];
					 $this->db->where('uid',$uid);
					 $data["youtube"] = $this->db->get('youtube_users')->result_array()[0];
					 $this->db->where('uid',$uid);
					 $data["facebook"] = $this->db->get('facebook')->result_array()[0];
					 $this->db->where('uid',$uid);
					 $data["twitter"] = $this->db->get('twitter_users')->result_array()[0];
				 }
				 $this->load->view("front/commons/head");
				 $this->load->view("front/manager_edit_influencer",$data);
				 $this->load->view("front/commons/bottom_dashboard",$data);
			 }else{
				 redirect(base_url()."home/manager/");
			 }
			}
		 }else if($type==='profile' && $this->session->userdata('logged_in_m')){
			 $id = $this->session->userdata('manager_id');
			 $data["manager"] = $this->user->getUserData($id,"manager");
			 
			 if($page!=="" && $page==="update"){
				 $media_file=null;
				 $new_name = time().$_FILES['profile_pic']['name'];
				 $target_file = FCPATH . "uploads/" . $new_name;
				 $update= Array();
				if (!empty($_FILES['profile_pic']['name'])) {
						if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
							$media_file = $new_name;
						}
				}
					 
				if($media_file){
							$update["picture"]=$this->helper->script_uri()."uploads/".$media_file;
				}
				
				if($this->input->post('password') !=null && $this->input->post('password')!==""){
					$update["password"] = md5($this->input->post('password'));
				}
				
				$update["name"] = $this->input->post('name');
				
				$this->db->where("id",$id);
				$this->db->update('managers',$update);
				
				if($this->db->affected_rows()>0){
					echo  json_encode(Array("status"=>"1","message"=>"Profile Updated successfully!"));
					return;
				}else{
					echo  json_encode(Array("status"=>"1","message"=>"Profile Updated successfully!"));
					return;
				}
				
			 }
			 
			  
			 $this->load->view("front/commons/head");
			 $this->load->view("front/manager_edit_profile",$data);
			 $this->load->view("front/commons/bottom_dashboard",$data);
		 }else if($type==="" && $page===""){
			 $this->load->view("front/commons/head");
			 $this->load->view("front/login_manager");
			 $this->load->view("front/commons/bottom"); 
		 }else{
			 header(base_url()."home/manager");
		 }
	}
	
	
	public function thumb($width = 150, $height = true) {

		 // download and create gd image
		 $url = $this->input->get("url");
		 echo $this->user->get_user_thumb($url);
	}
	
	
	public function resend_r_Email($action=""){
		if($action=="" && $this->input->get('utype')){
			 $table = $this->input->get('utype');
			 $this->load->view("front/commons/head");
			 $this->load->view("front/resend_confirm",["type"=>$table]);
			 $this->load->view("front/commons/bottom");
		
		}else if($action==="proceed"){
		
			$email = $this->input->post("res_email");
			 $status = false;
			 //Varify that user with this email exists
			 if($this->input->post("utype")){
				 $status = $this->user->resend_activation($email,$this->input->post("utype"));
				 $this->load->view("front/commons/head");
				 $this->load->view("front/resend_confirm",$status);
				 $this->load->view("front/commons/bottom"); 
			 }else{
				 redirect(base_url()); 
			 }
		}
	}
	
	
	public function mailTest(){
		$activation_url = base_url() . "home/user/confirm_email/12/"."xcccEX12XXXX" . "/?utype=users";
		$mail_message = "Thank you for registering with Dance Influencers, the only global platform for dance influencers in the World. We power influence marketing campaigns for brands and music labels across all verticals, and utilize every level of Dance Celebrity and Creator. Confirm your registration in the button below, and let's book some campaigns together.";
		$m = $this->mailer->influencerActivationMail("brokemaster101@gmail.com","Samiullah",$activation_url,$mail_message);
	}
}

	


