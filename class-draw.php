<?php



class ASPTdraw {
	//~~~~~
	static function pageCustomBox( $checked )
	{
		$CHECKED = ( $checked === 'true' ) ? ' CHECKED' : '';
		
		echo '<div id="enableProgressTracking_div" class="misc-pub-section" style="display:none;">';
		
		echo 	'<hr><h3>Progress Tracking</h3>';
		
		echo 	'<div id="ASPT_tickWrap" style="display:none;">';
		echo 		'<input name="enableProgressTracking" id="enableProgressTracking" type="checkbox"' . $CHECKED . '>';
		echo 		'<label for="enableProgressTracking">Enable subpage tracking</label>';
		echo 	'</div>';
		
		echo 	'<div id="ASPT_messageWrap" style="display:none;">';
		echo		'<span class="ASPTenabled">Tracking Enabled</span>';
		echo 	'</div>';
		
		echo '</div>';
		
		wp_nonce_field( 'ASPT_enableProgressTracking', 'ASPT_tickBox' );
	}
	
	
	
	//~~~~~
	static function specialThingy( $theContent )
	{
		$showTrackingOptions=false;
		// only progress is user is logged in! We cannot track pages for non logged in users
		if ( is_user_logged_in() )
		{
			$showTrackingOptions=true;
		} 	
	
		global $post;
		
		global $P_TRACKER;
		$ops = $P_TRACKER->ops;
		
		$thisPageID = get_the_ID();
		$userID = get_current_user_id();
		
		
		// Check to see if reset has been requested
		if(isset($_GET['action']))
		{
			if($_GET['action']=="resetProgress")
			{
				$ptrackerFeedback = '<div class="ptrackerFeedback">Progress reset!</div>';
				
				// Get all subpages and remove them
				$mySubPages = get_pages( array( 'child_of' =>$thisPageID,'sort_column' => 'menu_order' ) );
				foreach ( $mySubPages as $page ) 
				{
					
					$pageID =  $page->ID;				
					$P_TRACKER->deleteRow ( $userID, $pageID );
				}
			}
		}
		
		// Set some default vars
		$pTrackerNavStr='';
		$ptrackerDropdownNav='';
		$pTrackerMarkedStr='';
		
		// Now get the variables for each settings
		$defaults = $P_TRACKER->defaults();
		foreach ($defaults as $key => $value)
		{
			$$key = $ops[$key];
		}		

		
		if( $buttonIconID != 0 )
		{
			$nextButton = '<span>'.$nextLinkText.'</span><span><img src="'.PTRACKER_PLUGIN_URL.'/images/buttons/button'.$buttonIconID.'_next.png" border="0" width="50"></span>';
			$backButton = '<span><img src="'.PTRACKER_PLUGIN_URL.'/images/buttons/button'.$buttonIconID.'_back.png" border="0" width="50"></span><span>'.$backLinkText.'</span>';
		}
		else
		{
			$nextButton = $nextLinkText;
			$backButton = $backLinkText;
		}
		
		//If the auto mark as complete is on then mark this page
		if($autoMarkProgress=="on")
		{
			$P_TRACKER->addRow ( $userID, $thisPageID );
		}
		
		//---------------------------------------------------------------		
		// -------- Code for the mini menu on parent page
		//---------------------------------------------------------------
		$enableTrackingCheck = get_post_meta($thisPageID,'enableProgressTracking',true);
		//if($enableTrackingCheck=="on")
		if( $enableTrackingCheck == "true" )
		{
			
			$userTicks = $P_TRACKER->getRows( $userID );
			$mySubPages = get_pages( array( 'child_of' =>$thisPageID,'sort_column' => 'menu_order' ) );
		
			$subMenuStr= '<div id="learningObjectSubPageMenu">';
			
			if($subpageListStyle=="twoCol")
			{
				$subMenuStr.= '<div style="float:left; width:50%">';
			}
			$currentPage=1;
			
			$totalPages = count($mySubPages);
			$halfWay = round($totalPages/2);
		
			foreach ( $mySubPages as $page ) 
			{
				$pageID =  $page->ID;
				$link = get_page_link( $pageID );
				
				if($currentPage==1){$firstPageLink=$link;}
				
				$pageStatusIcon = 'red';
				$linkStyle='subMenuLinkUnread';
				if ( is_array( $userTicks ) ) {
					foreach ( $userTicks as $row ) {
						if ( $row->page_id == $pageID ) {
							$pageStatusIcon = 'green';
							$linkStyle = 'subMenuLinkRead';
							break;
						}
					}
				}



				$subMenuStr.='<span class="'.$linkStyle.'">';
				$subMenuStr.= '<a href="'.$link.'">';
				switch ($subPageNumberStyle)
				{
					case "numeric":
					case "":
						$subMenuStr.= $currentPage.'. ';					
					
					break;
					
					
					case "roman":				
						$subMenuStr.= ASPTutils::roman_numerals($currentPage).'. ';					
					break;
					
				}

				$subMenuStr.= $page->post_title;
				$subMenuStr.= '</a></span>';				
				
				$subMenuStr.='<br/>';
				
				// If its excerpt style then show the experpt
				if($subpageListStyle=="excerpt")
				{
					$subMenuStr.= progressTracker::get_excerpt_by_id ( $pageID);
				}
				
				
				if($currentPage==$halfWay && $subpageListStyle=="twoCol")
				{
					$subMenuStr.= '</div><div style="float:left; width:50%">';
				}
				$currentPage++;
			}	
			
		  
		  
			if($subpageListStyle=="twoCol") // If its two col then clsoe the extra div
			{
				$subMenuStr.= '</div>';
			}
			$subMenuStr.= '</div>';
			$subMenuStr.= '<div style="clear:both"></div><hr/>';
			  
			// Create teh progress bar if required
			$progressDial='';
			
			if($showStudentProgress<>"")
			{
				$args = array
				(
					"parent"=>get_the_ID(),
					"type" => $showStudentProgress
				);
				
				$progressDial = progressTracker::showUserProgress(  $args );
			}
			
			

			$theContent=$subMenuStr.$progressDial.$theContent; // Add the pages
			
			$theContent.='<div style="text-align:right">';
			$theContent.= '<a href="'.$firstPageLink.'" class="pure-button pure-button-primary ptracker-start-button thickbox">'.$startLinkText.'</a>';
			
			// Check to see if things can be rest
			if($allowUserReset=="on")
			{
				// Reset URL
				$resetURL = get_page_uri( $thisPageID ) ;
				
				$theContent.= '<a href="#TB_inline?height=220&amp;width=400&amp;inlineId=resetConfirmPopup" class="pure-button pure-button-secondary thickbox">Reset Progress</a>';
				$theContent.='<div id="resetConfirmPopup" style="display:none"><h3>Are you sure you want to reset your progress?</h3>';
				$theContent.='<div class="thickboxContent">This will reset all progress and cannot be undone!</div>';
				$theContent.='<a href="'.$resetURL.'?action=resetProgress" class="pure-button pure-button-primary">Yes reset my progress</a>';
				$theContent.='<a href="javascript:self.parent.tb_remove();" class="pure-button pure-button-secondary">Cancel</a>';								
				
				$theContent.='</div>'; //Close Thickbox
				
			}
			$theContent.='</div>'; // Close align right div for start button
			
			
		}
		
		//---------------------------------------------------------------		
		// -------- END Code for the mini menu on parent page
		//---------------------------------------------------------------		
		
		
		//---------------------------------------------------------------		
		// -------- Code for the next, back buttons and dropdown menu
		//---------------------------------------------------------------
		// If this is the parent of a page with content tracking then add next and back menu		
		if ($post->ancestors)
		{ 
			// Get the parent ID	
			$parentID = $post->ancestors[0];
			$parentTracking = get_post_meta($parentID,'enableProgressTracking',true);
			
			//if($parentTracking=="on") // the parent has tracking
			if( $parentTracking == "true" ) // the parent has tracking
			{
				$topicTitle =  get_the_title($parentID);  // Get the name of the parent ID	
				
				// Generate an array of the subpages of the parent. i.e. all the pages in the learning object
				$mySubPages = get_pages( array( 'child_of' =>$parentID,'sort_column' => 'menu_order' ) );
				
				$subPageCount = count($mySubPages);
				
				$subPages = array();
				
				foreach ($mySubPages as $page)
				{
					$subPages[] += $page->ID;
				}
				
				$currentPageNumber = array_search($post->ID, $subPages);
				
				
				$prevOffset = $currentPageNumber-1;
				if($prevOffset<=0)
				{
					$prevOffset=0;
				}
				$prevID = $mySubPages[$prevOffset];
				
				
				$nextOffset = $currentPageNumber+1;	
				$nextID ="";
				if($nextOffset>=$subPageCount)
				{
					$nextOffset=($subPageCount-1);
				}
				else
				{
					$nextID = $mySubPages[$nextOffset];	
				}
				
				$pTrackerNavStr.= '<div id="ptrackerNav">';
				//$pTrackerNavStr.= 'Page '.($currentPageNumber+1).'/'.$subPageCount.'<br/><br/>';
				if ($currentPageNumber>=1) {
					$pTrackerNavStr.= '<div id="ptrackerBackButtonDiv"><a href="'.get_permalink($prevID).'" title="Previous Page" alt="Previous Page">'.$backButton.'</a></div>';
				}
				if ($nextID)
				{
					$pTrackerNavStr.= '<div id="ptrackerNextButtonDiv"><a href="'.get_permalink($nextID).'" title="Next Page" alt="Next Page">'.$nextButton.'</a></div>';
				}
				
				$pTrackerNavStr.= '</div>';			
				
				
				
				// This adds the 'Mark as read' button
				$pTrackerToggler = "";
				if($showTrackingOptions==true && $autoMarkProgress<>"on" && $readButtonLocation<>"custom")
				{
					$pTrackerToggler = ASPTdraw::drawToggle();
				}
				
				
				// Build Drop down menu
				// Get the parent permalink for the menu dropdown
				$parentPermalink = get_permalink($post->post_parent);
					
				$ptrackerDropdownNav.= '<!-- navigation -->';
				
				$ptrackerDropdownNav.= '<div id="ptrackerJumpBox">';
				$ptrackerDropdownNav.= '<select name="page-dropdown"';
				$ptrackerDropdownNav.= 'onchange=\'document.location.href=this.options[this.selectedIndex].value;\'>';
				$ptrackerDropdownNav.= '<option value="">';
				$ptrackerDropdownNav.= esc_attr( __( 'Select page' ) );
				$ptrackerDropdownNav.= '</option>';
				
				$ptrackerDropdownNav.= '<option value="'.$parentPermalink.'">-- Topic Menu Page--</option>';					
				$currentPage=1;
				foreach ( $mySubPages as $page )
				{
					$option = '<option value="' . get_page_link( $page->ID ) . '">';
					
					switch ($subPageNumberStyle)
					{
						case "numeric":
						case "":
							$option.= $currentPage.'. ';					
						
						break;
						
						
						case "roman":				
							$option.= ASPTutils::roman_numerals($currentPage).'. ';					
						break;
						
					}					
					
					$option .= $page->post_title;
					$option .= '</option>';
					$ptrackerDropdownNav.= $option;
					$currentPage++;
				}
				$ptrackerDropdownNav.= '</select>';
				$ptrackerDropdownNav.= '</div>';	 // ENd of ptrackerJumpBox					
			}
			
			$pTrackerNavStr.= '<div style="clear:both"></div><hr/>';		// We need this regalress of logged in or not					
			// add nav
			if( $navButtonLocation == 'top' || $navButtonLocation == 'both' ) {
				$theContent = $pTrackerNavStr.$theContent; // Append new content to top of the main content
			}
			if( $navButtonLocation == 'bottom' || $navButtonLocation == 'both' ) {
				$theContent .= $pTrackerNavStr; // Append new content to bottom of the main content			
			}
			
			
			
		
			
			
			$showQuickJumpList = $showQuickJumpList;
			
			
			
			switch($showQuickJumpList)			
			{
				case "top":
					$theContent = $ptrackerDropdownNav . $theContent;
				break;
				
				case "both":
					$theContent = $ptrackerDropdownNav . $theContent . $ptrackerDropdownNav;
				break;
				
				case "none":
					// do nothing
				break;
				
				case "bottom":
				default:
					$theContent.=$ptrackerDropdownNav;
				break;				
				
			}
			
			
			// First append the marked as read options. Always need to do that
			
			switch($readButtonLocation)
			{
				case "bottom":
					$theContent = $theContent.$pTrackerToggler;
				break;
				
				case "top":
					$theContent = $pTrackerToggler.$theContent;
				break;
			}

		} 	
		
		
		// finally add the feedback div at the top if it exists
		if(isset($ptrackerFeedback))
		{
			$theContent = $ptrackerFeedback.$theContent;
		}
		
		return $theContent;
	}
	
	
	function drawToggle()
	{
		
		global $P_TRACKER;
		global $post;
		
		$ops = $P_TRACKER->ops;
		
		
		$unMarkedText = $ops['unMarkedText'];
		$markedText  = $ops['markedText'];
		
		// build marked string
		$userID = get_current_user_id();
		$userTicks = $P_TRACKER->getRows( $userID );
		$isTicked = 'red';
		$promptText = $unMarkedText;
		$promptClass = '';
		if ( is_array( $userTicks ) ) {
			foreach ( $userTicks as $row ) {
				if ( $row->page_id == $post->ID ) {
					$isTicked = 'green';
					$promptText = $markedText;
					$promptClass = 'isMarked';
					break;
				}
			}
		}		
		
		
		$pTrackerToggler="";	
		$pTrackerToggler.= '<div class="markAsReadWrap">';
		$pTrackerToggler.= '<span id="isMarkedText" class="' .$promptClass. '">' . $promptText . '</span>';
		$pTrackerToggler.= '<span id="markAsReadButton"><img id="markAsReadImage" src="' . PTRACKER_PLUGIN_URL . '/css/images/slidebutton-' .$isTicked . '.png"></span>';
		$pTrackerToggler.= '<span id="aspt_spinner"></span>';
		$pTrackerToggler.= '<input type="hidden" name="markAsReadButtonState" id="markAsReadButtonState" value="' .$isTicked . '" />';
		$pTrackerToggler.= '</div>';	
		$pTrackerToggler.= '<div style="clear:both"></div><hr/>';		// We need this regalress of logged in or not			
		
		
		return $pTrackerToggler;	
	}
}
?>