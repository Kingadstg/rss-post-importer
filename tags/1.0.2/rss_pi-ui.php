		<div class="wrap">
			<h2><?php _e("Rss import settings", 'rss_pi'); ?></h2>
			<form method="post">
				<input type="hidden" name="save_to_db" id="save_to_db" />
				<?php wp_nonce_field('settings_page','rss_pi_nonce'); ?>
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="postbox-container-1" class="postbox-container">
							<div class="postbox">
								<div class="inside">
									<div class="misc-pub-section">
										Latest import: <strong><?php echo($options['latest_import']); ?></strong>
									</div>
									<div id="major-publishing-actions">
										<input class="button button-primary button-large right" type="submit" name="info_update" value="<?php _e('Save'); ?>" />
										<input class="button button-large" type="submit" name="info_update" value="<?php _e('Save and import', "rss_pi"); ?>" id="save_and_import" />
									</div>
								</div>
							</div>
						</div>
						<div id="postbox-container-2" class="postbox-container">
							<table class="widefat rss_pi-table" id="rss_pi-table">
								<thead>
									<tr>
										<th><?php _e("Feed name", 'rss_pi'); ?></th>
										<th><?php _e("Feed url", 'rss_pi'); ?></th>
										<th><?php _e("Max posts / import", 'rss_pi'); ?></th>
										<th><?php _e("Category"); ?></th>
									</tr>
								</thead>
								<tbody class="rss-rows">
									<?php
										if(is_array($options['feeds']) && count($options['feeds']) > 0) :
											foreach($options['feeds'] as $f) :
												$category = get_the_category( $f['category_id'] );
												array_push($ids, $f['id']);
												include( plugin_dir_path( __FILE__ ) . 'parts/table_row.php');
											endforeach;
										else :
										?>
										<tr>
											<td colspan="4" class="empty_table">
												<?php _e('You haven\'t specified any feeds to import yet, why don\'t you <a href="#" class="add-row">add one now</a>?', "rss_pi"); ?>
											</td>
										</tr>
										<?php
										endif
									?>
								</tbody>
								<tfoot>
									<tr>
										<td colspan="4">
											<a href="#" class="button button-large button-primary add-row"><?php _e('Add new feed', "rss_pi"); ?></a>
											<input type="hidden" name="ids" id="ids" value="<?php echo(join($ids, ',')); ?>" />
										</td>
									</tr>
								</tfoot>
							</table>
							
							
						
								<table class="widefat rss_pi-table" id="rss_pi-table">
									<thead>
										<tr>
											<th colspan="5"><?php _e('Settings'); ?></th>
										</tr>
									</thead>
									<tbody class="setting-rows">
										<tr class="edit-row show">
											<td colspan="4">
												<table class="widefat edit-table">
													<tr>
														<td>
															<label for="frequency"><?php _e('Frequency', "rss_pi"); ?></label>
															<p class="description"><?php _e('How often will the import run.', "rss_pi"); ?></p>
														</td>
														<td>
															<select name="frequency" id="frequency">
																<?php $x = wp_get_schedules(); ?>
																<?php foreach(array_keys($x) as $interval) : ?>
																	<option value="<?php echo $interval; ?>" <?php if($options['settings']['frequency'] == $interval) : echo('selected="selected"'); endif; ?>><?php echo $x[$interval][ 'display' ]; ?></option>
																<?php endforeach; ?>
															</select>
														</td>
													</tr>
													<tr>
														<td>
															<label for="post_template"><?php _e('Template'); ?></label>
															<p class="description"><?php _e('This is how the post will be formatted.', "rss_pi"); ?></p>
															<p class="description">
																<?php _e('Available tags:', "rss_pi"); ?>
																<code>{$content}</code>
																<code>{$permalink}</code>
																<code>{$title}</code>
																<code>{$feed_title}</code>
															</p>
														</td>
														<td>
															<textarea name="post_template" id="post_template" cols="30" rows="10"><?php echo($options['settings']['post_template'] != '' ? $options['settings']['post_template'] : "{\$content}\nSource: {\$feed_title}"); ?></textarea>
														</td>
													</tr>
													<tr>
														<td><label for="post_status"><?php _e('Post status', "rss_pi"); ?></label></td>
														<td>
														
															<select name="post_status" id="post_status">
																<?php
																	$statuses = get_post_stati('', 'objects');
																	
																	foreach($statuses as $status)
																	{
																		?>
																			<option value="<?php echo($status->name);?>" <?php if($options['settings']['post_status'] == $status->name) : echo('selected="selected"'); endif; ?>><?php echo($status->label);?></option>
																		<?php
																	}
																?>
															</select>
														</td>
													</tr>
													<tr>
														<td><?php _e('Author'); ?></td>
														<td>
															<?php 
																$args = array(
																	'id' => 'author_id',
																	'name' => 'author_id',
																	'selected' => $options['settings']['author_id']
																);
																wp_dropdown_users( $args );
															?> 
														</td>
													</tr>
													<tr>
														<td><?php _e('Allow comments', "rss_pi"); ?></td>
														<td>
															<ul class="radiolist">
																<li>
																	<label><input type="radio" id="allow_comments" name="allow_comments" value="true" <?php echo($options['settings']['allow_comments'] == 'true' || $options['settings']['allow_comments'] == '' ? 'checked="checked"' : ''); ?> /> <?php _e('Yes'); ?></label>
																</li>
																<li>
																	<label><input type="radio" id="allow_comments" name="allow_comments" value="false" <?php echo($options['settings']['allow_comments'] == 'false' ? 'checked="checked"' : ''); ?> /> <?php _e('No'); ?></label>
																</li>
															</ul>
														</td>
													</tr>
													<tr>
														<td>
															<?php _e('Enable logging?', "rss_pi"); ?>
															<p class="description"><?php printf( __('The logfile can be found <a href="%s">here</a>.', "rss_pi"), $this->settings['dir'] . 'log.txt' ); ?></p>
														</td>
														<td>
															<ul class="radiolist">
																<li>
																	<label><input type="radio" id="enable_logging" name="enable_logging" value="true" <?php echo($options['settings']['enable_logging'] == 'true' ? 'checked="checked"' : ''); ?> /> <?php _e('Yes'); ?></label>
																</li>
																<li>
																	<label><input type="radio" id="enable_logging" name="enable_logging" value="false" <?php echo($options['settings']['enable_logging'] == 'false' || $options['settings']['enable_logging'] == '' ? 'checked="checked"' : ''); ?> /> <?php _e('No'); ?></label>
																</li>
															</ul>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
						<br class="clear" />
				</div>
			</form>
		</div>
