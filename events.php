<?php
/*
Plugin Name: Trainer Tower Events Widget
Plugin URI: https://github.com/code-around-corners/trainertower-events-widget
Description: This plugin adds an upcoming events and a recent results widget to WordPress.
Version: 0.1
Author: Timothy Crockford
Author URI: https://www.codearoundcorners.com/
License: GPL3
*/

class TTEvents_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'ttevents_widget',
			__( 'Trainer Tower Events', 'text_domain' ),
			array(
				'customize_selective_refresh' => true,
			)
		);
	}

	public function form( $instance ) {
		$defaults = array(
			'title'    => '',
            'show'     => '5',
		);
		
		extract( wp_parse_args((array)$instance, $defaults ) ); ?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Heading', 'text_domain' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'show' ); ?>"><?php _e( '# of Events Shown', 'text_domain' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'show' ); ?>" id="<?php echo $this->get_field_id( 'show' ); ?>" class="widefat">
			<?php
			$options = array(
				'1'        => __( 'Next Event', 'text_domain' ),
				'2'        => __( 'Next 2 Events', 'text_domain' ),
				'3'        => __( 'Next 3 Events', 'text_domain' ),
				'4'        => __( 'Next 4 Events', 'text_domain' ),
				'5'        => __( 'Next 5 Events', 'text_domain' ),
			);

			foreach ( $options as $key => $name ) {
				echo '<option value="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" '. selected( $show, $key, false ) . '>'. $name . '</option>';

			} ?>
			</select>
		</p>

	<?php }

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']    = isset( $new_instance['title'] ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['show']     = isset( $new_instance['show'] ) ? wp_strip_all_tags( $new_instance['show'] ) : '';
		return $instance;
	}

	public function widget( $args, $instance ) {
		extract( $args );

		$title    = isset( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : 'Upcoming Events';
		$show     = isset( $instance['show'] ) ? (int)$instance['show'] : 5;

		echo $before_widget;
		echo '<div class="widget-text wp_widget_plugin_box">';

		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		$filters = array(
		    "premierGroup"  => array("Regional Championship", "Special Championship", "International Championship"),
		    "startDate"     => date("Y-m-d"),
		    "product"		=> array("Video Game")
		);
		
		$defaultSocketTimeout = ini_get('default_socket_timeout');
		ini_set('default_socket_timeout', 5);
		$filtersEncoded = base64_encode(json_encode($filters));
		$tournaments = json_decode(@file_get_contents("https://www.pokecal.com/api.php?command=listEvents&filters=" . $filtersEncoded), true);
		ini_set('default_socket_timeout', $defaultSocketTimeout);
		
		$eventList = array();
			
		for ( $index = 0; $index < $show; $index++ ) {
			if ( stripos($tournaments["data"][$index]["premierEvent"], "Regional") !== false ) {
				$description = $tournaments["data"][$index]["city"] . " Regionals";
				
			} else if ( stripos($tournaments["data"][$index]["premierEvent"], "Special") !== false ) {
				$description = $tournaments["data"][$index]["city"] . " Special Event";
				
			} else if ( stripos($tournaments["data"][$index]["premierEvent"], "International") !== false ) {
				switch ( $tournaments["data"][$index]["isoCountryCode"] ) {
					case "AUS":
						$description = "Oceania International Championship";
						break;
						
					case "BRA":
						$description = "Latin America International Championship";
						break;
						
					case "GBR":
					case "DEU":
						$description = "European International Championship";
						break;
						
					case "USA":
						$description = "North American International Championship";
						break;
						
					default:
						$description = "International Championship";
						break;
				}
			} else {
				$description = $tournaments["data"][$index]["venueName"];
			}
			
			if ( ! isset($tournaments["data"][$index]["website"]) ) {
				$id = $tournaments["data"][$index]["tournamentID"];
				$url = "https://www.pokemon.com/us/play-pokemon/pokemon-events/" . substr($id, 0, 2) . "-" . substr($id, 2, 2) . "-" . substr($id, 4, 6) . "/";
			} else {
				$url = $tournaments["data"][$index]["website"];
			}
			
			$eventList[count($eventList)] = array(
				"date" => $tournaments["data"][$index]["date"],
				"description" => $description,
				"url" => $url,
				"countryCode" => $tournaments["data"][$index]["isoCountryCode"]
			);
		}
		
		display_event_list($eventList);

		echo '</div>';
		echo $after_widget;
	}
}

class TTResults_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'ttresults_widget',
			__( 'Trainer Tower Results', 'text_domain' ),
			array(
				'customize_selective_refresh' => true,
			)
		);
	}

	public function form( $instance ) {
		$defaults = array(
			'title'    => '',
            'show'     => '5',
		);
		
		extract( wp_parse_args((array)$instance, $defaults ) ); ?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Heading', 'text_domain' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'show' ); ?>"><?php _e( '# of Events Shown', 'text_domain' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'show' ); ?>" id="<?php echo $this->get_field_id( 'show' ); ?>" class="widefat">
			<?php
			$options = array(
				'1'        => __( 'Last Event', 'text_domain' ),
				'2'        => __( 'Last 2 Events', 'text_domain' ),
				'3'        => __( 'Last 3 Events', 'text_domain' ),
				'4'        => __( 'Last 4 Events', 'text_domain' ),
				'5'        => __( 'Last 5 Events', 'text_domain' ),
			);

			foreach ( $options as $key => $name ) {
				echo '<option value="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" '. selected( $show, $key, false ) . '>'. $name . '</option>';

			} ?>
			</select>
		</p>

	<?php }

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']    = isset( $new_instance['title'] ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['show']     = isset( $new_instance['show'] ) ? wp_strip_all_tags( $new_instance['show'] ) : '';
		return $instance;
	}

	public function widget( $args, $instance ) {
		extract( $args );

		$title    = isset( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : 'Upcoming Events';
		$show     = isset( $instance['show'] ) ? (int)$instance['show'] : 5;

		echo $before_widget;
		echo '<div class="widget-text wp_widget_plugin_box">';

		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		$defaultSocketTimeout = ini_get('default_socket_timeout');
		ini_set('default_socket_timeout', 5);
		$tournaments = json_decode(@file_get_contents("https://results.trainertower.com/api/v1/events"), true);
		ini_set('default_socket_timeout', $defaultSocketTimeout);
		
		$fullEventList = array();

        if ( count($tournaments) > 0 ) {
		    foreach($tournaments as $eventId => $tournament) {
			    $eventDate = $tournament["date"];
			
			    if ( ! isset($fullEventList[$eventDate]) ) {
				    $fullEventList[$eventDate] = array();
			    } 
			
			    $fullEventList[$eventDate][$eventId] = $tournament;
		    }
        }
		
		krsort($fullEventList);
		$eventsList = array();
		
		foreach($fullEventList as $eventList) {
			foreach($eventList as $event) {
				if ( count($eventsList) < $show ) {
					$eventsList[count($eventsList)] = array(
						"date" => strtotime($event["date"]),
						"description" => $event["name"],
						"url" => "https://results.trainertower.com/standings.php?id=" . $event["id"],
						"countryCode" => $event["countryCode"]
					);
				}
			}
		}
		
		display_event_list($eventsList);

		echo '</div>';
		echo $after_widget;
	}
}

function display_event_list($eventList) {
	$defaultSocketTimeout = ini_get('default_socket_timeout');
	ini_set('default_socket_timeout', 5);
	$countryList = json_decode(@file_get_contents("https://results.trainertower.com/api/v1/countries"), true);
	ini_set('default_socket_timeout', $defaultSocketTimeout);
	
?>
	<div class="container-fluid">
		<div class="row" style="background-color: #36456D; color: #ffffff;">
			<div class="col-3 col-md-4 col-lg-3 text-center text-center p-1"><b>Date</b></div>
			<div class="col-6 col-md-8 col-lg-6 text-center text-center p-1"><b>Location</b></div>
			<div class="d-block d-md-none d-lg-block col-3 col-lg-3 text-center p-1"><b>Country</b></div>
		</div>
<?php
	
		$index = 0;
		foreach($eventList as $key => $event) {
?>
        <div class="row<? echo (($index + 1) == count($eventList)) ? " rounded-bottom" : ""; ?><? echo ($index % 2 == 1) ? " bg-light" : ""; ?>" style="color: #36456D;">
			<div class="col-3 col-md-4 col-lg-3 text-center my-auto"><strong><? echo date("M j", $event["date"]); ?></strong></div>
			<div class="col-6 col-md-8 col-lg-6 text-center my-auto">
				<a href="<? echo $event["url"]; ?>">
					<span class="d-none d-md-block d-lg-none"><? echo $countryList[$event["countryCode"]]["flagEmoji"]; ?></span>
					<? echo $event["description"]; ?>
				</a>
			</div>
			<div class="d-block d-md-none d-lg-block col-3 col-lg-3 text-center my-auto">
				<img class="image-fit" src="https://results.trainertower.com/<? echo $countryList[$event["countryCode"]]["flagUrl"]; ?>" />
			</div>
		</div>
<?php
			$index++;
		}
?>
	</div>
<?php
}
	
function ttevents_register_widget() {
	register_widget( 'TTEvents_Widget' );
	register_widget( 'TTResults_Widget' );
}

add_action( 'widgets_init', 'ttevents_register_widget' );
