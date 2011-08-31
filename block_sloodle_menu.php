<?php

/**
* Defines the Sloodle menu block class.
* 
* @package sloodle
* @contributor Peter R. Bloomfield
*/

/** Include the current Sloodle configuration, if possible. */
@include_once($CFG->dirroot .'/mod/sloodle/sl_config.php');
if (defined('SLOODLE_LIBROOT')) {
    /** Inlcude the current general Sloodle functionality. */
    require_once(SLOODLE_LIBROOT.'/general.php');
    /** Include the Sloodle course functionality. */
    require_once(SLOODLE_LIBROOT.'/course.php');
}

/** Include the old Sloodle configuration, in case the module is out-dated. */
if (!defined('SLOODLE_VERSION')) {
    @include_once($CFG->dirroot .'/mod/sloodle/config.php');
    if (defined('SLOODLE_DIRROOT')) {
        /** Include the old general Sloodle functionality. */
        require_once(SLOODLE_DIRROOT.'/lib/sl_generallib.php');
    }
}

/** Define the Sloodle Menu Block version. */
define('SLOODLE_MENU_VERSION', 2.0);

/**
* Defines the block class.
* @package sloodle
*/
class block_sloodle_menu extends block_base {

    /**
    * Perform block initialisation.
    * @return void
    */
    function init() {
        global $CFG;
        
        $this->title = get_string('blockname', 'block_sloodle_menu');
        $this->content_type = BLOCK_TYPE_TEXT;
        $this->version = 2010110311;
    }
    
    /**
    * Indicates whether or not this module has a global configuration page.
    * @return bool True if there is a global configuration page, or false otherwise.
    */
    function has_config() {
        return false;
    }
    
    /**
    * Indicates whether or not to hide the header of this block.
    * @return bool True to hide the header, or false to show it.
    */
    function hide_header() {
        return false;
    }

    /**
    * Defines *and* returns the content of this block.
    * @return object
    */
    function get_content() {
        global $CFG, $COURSE, $USER;
        
        // Construct the content
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
        
        // If no course has been specified, then we are using the site course
        if (!isset($COURSE)) {
            $COURSE = get_site();
        }
        
        // If the user is not logged in or if they are using guest access, then we can't show anything
        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }
        
        // Get the context instance for this course
        $course_context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        
        // This version of the menu isn't compatible with older version of the Sloodle module
        if (defined('SLOODLE_VERSION') && SLOODLE_VERSION < 0.4) {
            $this->content->text = get_string('oldmodule', 'block_sloodle_menu');
            return $this->content;
        }
        
        // Has the Sloodle activity module been installed?
        if (!(function_exists("sloodle_is_installed") && sloodle_is_installed())) {
            $this->content->text = get_string('sloodlenotinstalled', 'block_sloodle_menu');
            return $this->content;
        }
        
        // Get the Sloodle course data
        $sloodle_course = new SloodleCourse();
        if (!$sloodle_course->load((int)$COURSE->id)) {
            $this->content->text = get_string('failedloadcourse', 'block_sloodle_menu');
            return $this->content;
        }
        
        // Add the Sloodle and Sloodle Menu version info to the footer of the block
        $this->content->footer = '<span style="color:#565656;font-style:italic; font-size:10pt;">'.get_string('sloodlemenuversion', 'block_sloodle_menu').': '.(string)SLOODLE_MENU_VERSION.'</span>';
        $this->content->footer .= '<br/><span style="color:#888888;font-style:italic;font-size:8pt;">'.get_string('sloodleversion', 'block_sloodle_menu').': '.(string)SLOODLE_VERSION.'</span>';
        
        // Attempt to find a Sloodle user for the Moodle user
        $dbquery = "    SELECT * FROM {$CFG->prefix}sloodle_users
                        WHERE userid = ? AND NOT (avname = '' AND uuid = '')
                    ";
        $dbresult = sloodle_get_records_sql_params($dbquery, array($USER->id));
        $sl_avatar_name = "";
        if (!is_array($dbresult) || count($dbresult) == 0) $userresult = FALSE;
        else if (count($dbresult) > 1) $userresult = "Multiple avatars associated with your Moodle account.";
        else {
            $userresult = TRUE;
            reset($dbresult);
            $cur = current($dbresult);
            $sl_avatar_name = $cur->avname;
        }
        
        if ($userresult === TRUE) {
            // Success
            // Make sure there was a name
            if (empty($sl_avatar_name)) $sl_avatar_name = '('.get_string('nameunknown', 'block_sloodle_menu').')';
            $this->content->text .= '<center><span style="font-size:10pt;font-style:italic;color:#777777;">'.get_string('youravatar', 'block_sloodle_menu').':</span><br/>';
            
            // Make the avatar name a link if the user management page exists
            $this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/view.php?_type=user&amp;id={$USER->id}&amp;course={$COURSE->id}\">$sl_avatar_name</a>";
            $this->content->text .= '<br/></center>';
            
        } else if (is_string($userresult)) {
            // An error occurred
            $this->content->text .= '<center><span style="font-size:10pt;font-style:italic;color:#777777;">'.get_string('youravatar', 'block_sloodle_menu').':</span><br/>ERROR ('.$userresult.')</center>';
            
        } else {
            // No avatar linked yet
            $this->content->text .= '<center><span style="font-style:italic;">('.get_string('noavatar', 'block_sloodle_menu').')</span></center>';
        }
        
        // Add links to common Sloodle stuff
        $this->content->text .= '<div style="padding:1px; margin-top:4px; margin-bottom:4px; border-top:solid 1px #cccccc; border-bottom:solid 1px #cccccc;">';

        // Add the Sloodle profile link
        $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/user.gif\" width=\"16\" height=\"16\"/> ";
        $this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/view.php?_type=user&amp;id={$USER->id}&amp;course={$COURSE->id}\">".get_string('mysloodleprofile', 'block_sloodle_menu')."</a><br/>";

        // Show a link to all Sloodle activities on this course
        //TODO: possibly show number of visible Sloodle activities?
        $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/boxes.gif\" width=\"16\" height=\"16\"/> ";
        $this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/index.php?id={$COURSE->id}\">".get_string('sloodleactivities', 'block_sloodle_menu')."</a><br/>";
        
        // Do we have LoginZone data for this course?
        if ($sloodle_course->has_loginzone_data()) {
            // Show a link to the LoginZone for this course
            $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/loginzone.gif\" width=\"16\" height=\"16\"/> ";
            $this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/classroom/loginzone.php?id={$COURSE->id}\">".get_string('courseloginzone', 'block_sloodle_menu')."</a><br/>";
        }
        
        //$this->content->text .= '<hr>';
        
        // Add a link for avatars list
        $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/user_mng.gif\" width=\"16\" height=\"16\"/> ";
        $this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/view.php?_type=users&amp;course={$COURSE->id}\">".get_string('avatars', 'block_sloodle_menu')."</a><br/>";            
        
        // Add a link to Sloodle course settings, if the user can update the course
        if (has_capability('moodle/course:update', $course_context)) {
            $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/page.gif\" width=\"16\" height=\"16\"/> ";
            $this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/view.php?_type=course&amp;id={$COURSE->id}\">".get_string('editcourse', 'block_sloodle_menu')."</a><br>\n";
        }
        // Add a link to Sloodle logs, if the user can update the course
        //if (has_capability('moodle/course:update', $course_context)) {
            //$this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/notes.gif\" width=\"16\" height=\"16\"/> ";
            //$this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/view.php?_type=logs&id={$COURSE->id}\">".get_string('logs:view', 'sloodle')."</a><br>\n";
        //}
        // Add a link to Sloodle Backpack
        
            $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/coin.png\" width=\"16\" height=\"16\"/> ";
            $this->content->text .= "<a href=\"{$CFG->wwwroot}/mod/sloodle/view.php?_type=backpack&id={$COURSE->id}\">".get_string('backpack:view', 'sloodle')."</a><br>\n";
        
        // Add a module configuration link if the user has authority to administer the module
        if (has_capability('moodle/site:config', $course_context)) {
            
            // The address of the configuration page depends on our version of Moodle
            if ($CFG->version < 2007101500) {
                // < 1.9
                $address = "/admin/module.php?module=sloodle";
            } else {
                // >= 1.9
                $address = "/admin/settings.php?section=modsettingsloodle";
            }
        
            $this->content->text .= "<img src=\"{$CFG->wwwroot}/blocks/sloodle_menu/img/configure.gif\" width=\"16\" height=\"16\"/> ";
            $this->content->text .= "<a href=\"{$CFG->wwwroot}$address\">".get_string('sloodleconfig', 'block_sloodle_menu')."</a><br/>";
        }
        
        $this->content->text .= '</div>';
        
        return $this->content;
    }
}

?>
