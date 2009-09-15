<?php defined('SYSPATH') OR die('No direct access allowed.');
/*
 * Twitter demo controller
 *
 * @package    Twitter
 * @author     Justin Hernandez <justin@transphorm.com>
 * @license    http://transphorm.com/license.txt
 */

class Controller_TwitterDemo extends Kohana_Controller_Template {

	// template
	public $template = false;
	// autoload
	public $auto_render = false;


	
	public function before()
	{
		parent::before();
		
		/* twitter sandbox for the kohanatwitlib, if you are going to do
		 * extensive testing please create your own account
		 * same password for both accounts
		 */
		$username = 'kohanatwitlib';
		//$username = 'kohanatwitdev';
		$password = 'kohana';
		$this->t = Twitter::instance($username, $password);
		$this->u = $username;
		print('<h1>'.$this->request->param('action').'</h1>');
		print("<h2>user: $username</h2>");
	}

	public function action_index()
	{
		// base url
		$base = $this->request->uri;
		// get methods
		$methods = new ArrayIterator(get_class_methods($this));
		// methods to ignore
		$ignore = array(
							'__construct',
							'action_index',
							'__call',
							'_kohana_load_view',
							'before'
						);

		// print demo links
		while ($methods->valid())
		{
			$c = $methods->current();
			if (!in_array($c, $ignore))
			{
				$c = str_replace('action_', '', $c);
				print "<a style='margin-left:25px' href='$base/$c'>".$c.'</a><br/>';
			}
			$methods->next();
		}
	}

	public function action_public_timeline()
	{
		Twitter::instance()->format('xml')->public_timeline();
	}

	public function action_friends_timeline()
	{
		Twitter::instance()->format('json')->friends_timeline(NULL, NULL, 1);
	}

	public function action_user_timeline()
	{
		$this->t->user_timeline('shadowhand', 10);
	}

	public function action_show_status()
	{
		$this->t->show_status(808);
	}

	public function action_update_status()
	{
		if (!$_POST)
		{
			print "Status<br/><form method='post'><input type='text' name='status' maxlength='140'>";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->format('json')->update_status($_POST['status']);
		}
	}

	public function action_replies()
	{
		$this->t->replies();
	}

	public function action_destroy_status()
	{
		if (!$_POST)
		{
			print "<a href='http://twitter.com/kohanatwitlib'>Choose status from here</a>";
			print "<br/><br/><form method='post'><input type='text' name='status_id'><input type='submit'/></form>";
		}
		else
		{
			$this->t->destroy_status($_POST['status_id']);
		}
	}

	public function action_friends()
	{
		$this->t->friends('biz', 2);
	}

	public function action_followers()
	{
		$this->t->followers('jack', 2);
	}

	public function action_user()
	{
		if (!$_POST)
		{
			print "Get user info for:<br/><form method='post'><input type='text' name='id'>";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->user($_POST['id']);
		}
	}

	public function action_direct_messages()
	{
		$this->t->format('rss')->direct_messages();
	}

	public function action_sent_messages()
	{
		$this->t->format('xml')->sent_messages();
	}

	public function action_new_message()
	{
		if (!$_POST)
		{
			print "<h4>In order to send messages there has to be an existing relationship</h4>";
			print "<br/>Send Message:<br/><form method='post'><input type='text' name='message' maxlength='140'>";
			print "<br/>To:<br/><input type='text' name='user' />";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$t = new Twitter('kohanatwitdev', 'kohana');
			$t->new_message($_POST['user'], $_POST['message']);
		}
	}

	public function action_destroy_message()
	{
		if (!$_POST)
		{
			print "<a href='".request::referrer()."/direct_messages'>Direct message here</a><br/>";
			print "<a href='".request::referrer()."/sent_messages'>Sent message here</a>";
			print "<br/><br/><form method='post'><input type='text' name='id'><input type='submit'/></form>";
		}
		else
		{
			$this->t->format('xml')->destroy_message($_POST['id']);
		}
	}
	
	public function action_create_friendship()
	{
		if (!$_POST)
		{
			print "Befriend whom?<br/><form method='post'><input type='text' name='id'>";
			print "<br/><input type='checkbox' value='TRUE' name='notify' />notify?";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->create_friendship($_POST['id']);
		}
	}

	public function action_destroy_friendship()
	{
		if (!$_POST)
		{
			print "End friendship with whom?<br/><form method='post'><input type='text' name='id'>";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->destroy_friendship($_POST['id']);
		}
	}

	public function action_friendship_exists()
	{
		if (!$_POST)
		{
			print "Check existing friendship<br/><form method='post'>";
			print "<br/>User A:<input type='text' name='a'>";
			print "<br/>User B:<input type='text' name='b'>";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->friendship_exists($_POST['a'], $_POST['b']);
		}
	}

	public function action_friend_ids()
	{
		if (!$_POST)
		{
			print "Get friend ids for (leave blank for $this->u):<br/><form method='post'><input type='text' name='id'>";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->friend_ids($_POST['id']);
		}
	}

	public function action_follower_ids()
	{
		if (!$_POST)
		{
			print "Get follower ids for (leave blank for $this->u):<br/><form method='post'><input type='text' name='id'>";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->follower_ids($_POST['id']);
		}
	}

	public function action_verify_credentials()
	{
		$this->t->verify_credentials();
	}

	public function action_update_delivery_device()
	{
		if (!$_POST)
		{
			print "sms, im, or none:<br/><form method='post'><input type='text' name='id'>";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->update_delivery_device($_POST['id']);
		}
	}

	public function action_update_profile_colors()
	{
		if (!$_POST)
		{
			print "<h4>Do not us # with color values</h4>";
			print "<form method='post'>";
			print "<br/>BG color:<input type='text' name='bg'>";
			print "<br/>Text color:<input type='text' name='text'>";
			print "<br/>Link color:<input type='text' name='link'>";
			print "<br/>Sidebar fill color:<input type='text' name='sidefill'>";
			print "<br/>Sidebar border color:<input type='text' name='sideborder'>";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->update_profile_colors($_POST['bg'], $_POST['text'], $_POST['link'], $_POST['sidefill'], $_POST['sideborder']);
		}
	}

	public function action_update_profile_image()
	{
		if (!$_FILES)
		{
			print "Must be a valid GIF, JPG, or PNG image of less than 700 kilobytes";
			print "in size.  Images with width larger than 500 pixels will be scaled down.";
			print "<br/><form action='' enctype='multipart/form-data' method='post'><input type='file' name='image'>";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->update_profile_image(file_get_contents($_FILES['image']['tmp_name']), $_FILES['image']['type']);
		}
	}

	public function action_update_profile_background_image()
	{
		if (!$_FILES)
		{
			print "Must be a valid GIF, JPG, or PNG image of less than 800 kilobytes in size.  Images with width larger than 2048 pixels will be scaled down.";
			print "<br/><form action='' enctype='multipart/form-data' method='post'><input type='file' name='image'>";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->update_profile_background_image(file_get_contents($_FILES['image']['tmp_name']), $_FILES['image']['type']);
		}
	}

	public function action_rate_limit_status()
	{
		$this->t->rate_limit_status();
	}

	public function action_update_profile()
	{
		if (!$_POST)
		{
			print "<form method='post'>";
			print "<br/>Name:<input type='text' name='name'>";
			print "<br/>Email:<input type='text' name='email'>";
			print "<br/>Url:<input type='text' name='url'>";
			print "<br/>Location:<input type='text' name='loc'>";
			print "<br/>Description:<input type='text' name='des'>";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->update_profile($_POST['name'], $_POST['email'], $_POST['url'], $_POST['loc'], $_POST['des']);
		}
	}

	public function action_favorites()
	{
		if (!$_POST)
		{
			print "grab favorites for (leave blank for $this->u):<br/><form method='post'><input type='text' name='id'>";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->favorites($_POST['id']);
		}
	}

	public function action_create_favorite()
	{
		if (!$_POST)
		{
			print "Status ID:<br/><form method='post'><input type='text' name='id'><input type='submit'/></form>";
		}
		else
		{
			$this->t->format('json')->create_favorite($_POST['id']);
		}
	}

	public function action_destroy_favorite()
	{
		if (!$_POST)
		{
			print "Status ID:<br/><form method='post'><input type='text' name='id'><input type='submit'/></form>";
		}
		else
		{
			$this->t->destroy_favorite($_POST['id']);
		}
	}

	public function action_follow()
	{
		if (!$_POST)
		{
			print "Follow whom?<br/><form method='post'><input type='text' name='id'>";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->follow($_POST['id']);
		}
	}

	public function action_leave()
	{
		if (!$_POST)
		{
			print "Leave whom?<br/><form method='post'><input type='text' name='id'>";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->leave($_POST['id']);
		}
	}

	public function action_create_block()
	{
		if (!$_POST)
		{
			print "Block whom?<br/><form method='post'><input type='text' name='id'>";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->create_block($_POST['id']);
		}
	}

	public function action_destroy_block()
	{
		if (!$_POST)
		{
			print "Unblock whom?<br/><form method='post'><input type='text' name='id'>";
			print "<br/><input type='submit'/></form>";
		}
		else
		{
			$this->t->destroy_block($_POST['id']);
		}
	}

	public function action_test()
	{
		$this->t->test();
	}

	public function action_search()
	{
		$this->t->search('kohana');
	}

	public function action_search_advanced()
	{
		$this->t->search('kohana+easy');
	}

	public function action_search_from()
	{
		$this->t->search_from('zeelot3k');
	}

	public function action_search_to()
	{
		$this->t->search_to('PolarisDigital');
	}

	public function action_search_user()
	{
		$this->t->search_user('Shadowhand');
	}

	public function action_search_hash()
	{
		$this->t->search_hash('php');
	}

	public function action_current_trends()
	{
		$this->t->current_trends();
	}

	public function action_daily_trends()
	{
		$this->t->daily_trends('2009-03-19', 'hashtags');
	}

	public function action_weekly_trends()
	{
		$this->t->weekly_trends('2009-03-19');
	}

}
