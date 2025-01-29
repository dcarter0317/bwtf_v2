<?php
/**
 * Implementation of hook_form_system_theme_settings_alter()
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 *
 * @param $form_state
 *   A keyed array containing the current state of the form.
 */

 function bwtf_form_system_theme_settings_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state){
    $form['bwtf_settings'] = array(
        '#type' => 'fieldset',
        '#title' => t('Slider Settings'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );
      $form['bwtf_settings']['slider'] = array(
        '#type' => 'fieldset',
        '#title' => t('HomePage Slider'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      );
      $form['bwtf_settings']['slider']['slider_display'] = array(
        '#type' => 'checkbox',
        '#title' => t('Show Slider'),
        '#default_value' => theme_get_setting('slider_display','bwtf'),
        '#description'   => t("Check this option to show Slider on the homepage. Uncheck to hide."),
      );
      $form['bwtf_settings']['slider']['slide'] = array(
        '#markup' => t('You can change the description and URL of each slide in the following Slide Setting fieldsets.'),
      );
      // slide1
      $form['bwtf_settings']['slider']['slide1'] = array(
        '#type' => 'fieldset',
        '#title' => t('Slide 1'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );
      $form['bwtf_settings']['slider']['slide1']['slide1_title'] = array(
        '#type' => 'textfield',
        '#title' => t('Slide Title'),
        '#default_value' => theme_get_setting('slide1_title','bwtf'),
      );
      $form['bwtf_settings']['slider']['slide1']['slide1_desc'] = array(
        '#type' => 'textarea',
        '#title' => t('Slide Description'),
        '#default_value' => theme_get_setting('slide1_desc','bwtf'),
      );
      $form['bwtf_settings']['slider']['slide1']['slide1_url'] = array(
        '#type' => 'textfield',
        '#title' => t('Slide URL'),
        '#default_value' => theme_get_setting('slide1_url','bwtf'),
      );
      $form['bwtf_settings']['slider']['slide1']['slide1_link_text'] = array(
        '#type' => 'textfield',
        '#title' => t('Slide Link Text'),
        '#default_value' => theme_get_setting('slide1_link_text','bwtf'),
      );
      $form['bwtf_settings']['slider']['slide1']['slide1_image'] = array(
        '#type' => 'managed_file',
        '#title' => t('Slide Image'),
        '#default_value' => theme_get_setting('slide1_image','bwtf'),
        '#upload_location' => 'public://',
      );

      // slide2
      $form['bwtf_settings']['slider']['slide2'] = array(
        '#type' => 'fieldset',
        '#title' => t('Slide 2'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );
      $form['bwtf_settings']['slider']['slide2']['slide2_title'] = array(
        '#type' => 'textfield',
        '#title' => t('Slide Title'),
        '#default_value' => theme_get_setting('slide2_title','bwtf'),
      );
      $form['bwtf_settings']['slider']['slide2']['slide2_desc'] = array(
        '#type' => 'textarea',
        '#title' => t('Slide Description'),
        '#default_value' => theme_get_setting('slide2_desc','bwtf'),
      );
      $form['bwtf_settings']['slider']['slide2']['slide2_url'] = array(
        '#type' => 'textfield',
        '#title' => t('Slide URL'),
        '#default_value' => theme_get_setting('slide2_url','bwtf'),
      );
      $form['bwtf_settings']['slider']['slide2']['slide2_link_text'] = array(
        '#type' => 'textfield',
        '#title' => t('Slide Link Text'),
        '#default_value' => theme_get_setting('slide2_link_text','bwtf'),
      );
      $form['bwtf_settings']['slider']['slide2']['slide2_image'] = array(
        '#type' => 'managed_file',
        '#title' => t('Slide Image'),
        '#default_value' => theme_get_setting('slide2_image','bwtf'),
        '#upload_location' => 'public://',
      );
   
      // slide3
      $form['bwtf_settings']['slider']['slide3'] = array(
        '#type' => 'fieldset',
        '#title' => t('Slide 3'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );
      $form['bwtf_settings']['slider']['slide3']['slide3_title'] = array(
        '#type' => 'textfield',
        '#title' => t('Slide Title'),
        '#default_value' => theme_get_setting('slide3_title','bwtf'),
      );
      $form['bwtf_settings']['slider']['slide3']['slide3_desc'] = array(
        '#type' => 'textarea',
        '#title' => t('Slide Description'),
        '#default_value' => theme_get_setting('slide3_desc','bwtf'),
      );
      $form['bwtf_settings']['slider']['slide3']['slide3_url'] = array(
        '#type' => 'textfield',
        '#title' => t('Slide URL'),
        '#default_value' => theme_get_setting('slide3_url','bwtf'),
      );
      $form['bwtf_settings']['slider']['slide3']['slide3_link_text'] = array(
        '#type' => 'textfield',
        '#title' => t('Slide Link Text'),
        '#default_value' => theme_get_setting('slide3_link_text','bwtf'),
      );
      $form['bwtf_settings']['slider']['slide3']['slide3_image'] = array(
        '#type' => 'managed_file',
        '#title' => t('Slide Image'),
        '#default_value' => theme_get_setting('slide3_image','bwtf'),
        '#upload_location' => 'public://',
      );

      // slide4
      $form['bwtf_settings']['slider']['slide4'] = array(
        '#type' => 'fieldset',
        '#title' => t('Slide 4'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );
      $form['bwtf_settings']['slider']['slide4']['slide4_title'] = array(
        '#type' => 'textfield',
        '#title' => t('Slide Title'),
        '#default_value' => theme_get_setting('slide4_title','bwtf'),
      );
      $form['bwtf_settings']['slider']['slide4']['slide4_desc'] = array(
        '#type' => 'textarea',
        '#title' => t('Slide Description'),
        '#default_value' => theme_get_setting('slide4_desc','bwtf'),
      );
      $form['bwtf_settings']['slider']['slide4']['slide4_url'] = array(
        '#type' => 'textfield',
        '#title' => t('Slide URL'),
        '#default_value' => theme_get_setting('slide4_url','bwtf'),
      );
      $form['bwtf_settings']['slider']['slide4']['slide4_link_text'] = array(
        '#type' => 'textfield',
        '#title' => t('Slide Link Text'),
        '#default_value' => theme_get_setting('slide4_link_text','bwtf'),
      );
      $form['bwtf_settings']['slider']['slide4']['slide4_image'] = array(
        '#type' => 'managed_file',
        '#title' => t('Slide Image'),
        '#default_value' => theme_get_setting('slide4_image','bwtf'),
        '#upload_location' => 'public://',
      );

    // slide5
     $form['bwtf_settings']['slider']['slide5'] = array(
         '#type' => 'fieldset',
        '#title' => t('Slide 5'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        );
     $form['bwtf_settings']['slider']['slide5']['slide5_title'] = array(
        '#type' => 'textfield',
        '#title' => t('Slide Title'),
        '#default_value' => theme_get_setting('slide5_title','bwtf'),
        );
     $form['bwtf_settings']['slider']['slide5']['slide5_desc'] = array(
        '#type' => 'textarea',
        '#title' => t('Slide Description'),
        '#default_value' => theme_get_setting('slide5_desc','bwtf'),
        );
     $form['bwtf_settings']['slider']['slide5']['slide5_url'] = array(
        '#type' => 'textfield',
        '#title' => t('Slide URL'),
        '#default_value' => theme_get_setting('slide5_url','bwtf'),
        );
        $form['bwtf_settings']['slider']['slide5']['slide5_link_text'] = array(
          '#type' => 'textfield',
          '#title' => t('Slide Link Text'),
          '#default_value' => theme_get_setting('slide5_link_text','bwtf'),
        );   
     $form['bwtf_settings']['slider']['slide5']['slide5_image'] = array(
        '#type' => 'managed_file',
        '#title' => t('Slide Image'),
        '#default_value' => theme_get_setting('slide5_image','bwtf'),
        '#upload_location' => 'public://',
        );

      // slide6
      $form['bwtf_settings']['slider']['slide6'] = array(
        '#type' => 'fieldset',
       '#title' => t('Slide 6'),
       '#collapsible' => TRUE,
       '#collapsed' => TRUE,
       );
    $form['bwtf_settings']['slider']['slide6']['slide6_title'] = array(
       '#type' => 'textfield',
       '#title' => t('Slide Title'),
       '#default_value' => theme_get_setting('slide6_title','bwtf'),
       );
    $form['bwtf_settings']['slider']['slide6']['slide6_desc'] = array(
       '#type' => 'textarea',
       '#title' => t('Slide Description'),
       '#default_value' => theme_get_setting('slide6_desc','bwtf'),
       );
    $form['bwtf_settings']['slider']['slide6']['slide6_url'] = array(
       '#type' => 'textfield',
       '#title' => t('Slide URL'),
       '#default_value' => theme_get_setting('slide6_url','bwtf'),
       );
    $form['bwtf_settings']['slider']['slide6']['slide6_link_text'] = array(
        '#type' => 'textfield',
        '#title' => t('Slide Link Text'),
        '#default_value' => theme_get_setting('slide6_link_text','bwtf'),
      );   
    $form['bwtf_settings']['slider']['slide6']['slide6_image'] = array(
       '#type' => 'managed_file',
       '#title' => t('Slide Image'),
       '#default_value' => theme_get_setting('slide6_image','bwtf'),
       '#upload_location' => 'public://',
       );
  

      $form['bwtf_settings']['slideshow']['slideimage'] = array(
        '#markup' => t('To change the default Slide Images, Replace the slide-1.jpg, slide-2.jpg, slide-3.jpg, slide-4.jpg, slide-5.jpg and slide-6.jpg in the images folder of the theme folder.'),
      );
 }  