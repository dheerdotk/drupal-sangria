function getCsrfToken(callback) {
  jQuery.get(Drupal.url('session/token'))
      .done(function (data) {
          var csrfToken = data;
          callback(csrfToken);
      });
}

function postNode(csrfToken, node, form1) {

  var getUrl = window.location;
  var baseUrl = getUrl .protocol + "//" + getUrl.host + "/" + getUrl.pathname.split('/')[1];
 
  if(getUrl.host=="localhost")
  {
    baseUrl = baseUrl+"/drupal-9.3.12/drupal-9.3.12/"
  }

  var url = baseUrl+"user_points_api/user_points_resource"

  jQuery.ajax({
      url: url+'?_format=json',
      method: 'POST',
      headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken
      },
      data: JSON.stringify(node),
      success: function (node) {
          console.log(node);
      },
      error: function (XMLHttpRequest, textStatus, errorThrown) {
          console.log("Status: " + textStatus);
          console.log("Error: " + errorThrown);
      },
      timeout: 5000 // sets timeout to 5 seconds
  }).done(function() {
    form1.submit();
    return true;
  });
}


jQuery('#edit-submit').click(function(e) {
  e.preventDefault();
  var options = JSON.parse(document.querySelector('script[data-drupal-selector="drupal-settings-json"]').innerText);
  var path_split = options.path.currentPath.toString().split("/");  
  var entity_id = path_split[1];
  var form1 = jQuery(this).closest("form");
  var user_id = form1.attr('id');
  var content_types = ["node-recipes-edit-form", "node-health-journeys-edit-form", "node-self-cure-blogs-edit-form", "node-question-answers-edit-form", "node-answers-edit-form"]
  
    if(jQuery.inArray(user_id, content_types) !== -1)
    {
      console.log("inside  checking");
      var entity_type = "";
      switch(user_id){
        case "node-recipes-edit-form" : 
          entity_type = "recipes";
          break;
        case "node-health-journeys-edit-form" : 
          entity_type = "health-journeys";
          break;
        case "node-self-cure-blogs-edit-form" : 
          entity_type = "self-cure-blogs";
          break;
        case "node-article-edit-form" : 
          entity_type = "articles";
          break;
        case "node-question-answers-edit-form" : 
          entity_type = "question-answers";
          break;
        case "node-answers-edit-form": 
          entity_type = "answers";
          break;
      }

      var newNode = {         
          entityType: entity_type,
          isPublished: jQuery('#edit-field-is-published').val(),
          uid: jQuery('#edit-uid-0-target-id').val(),
          createdDate: jQuery('#edit-field-published-at-0-value-date').val(),
          createdTime: jQuery('#edit-field-published-at-0-value-time').val(),
          entity_id: entity_id         
      };
      getCsrfToken(function (csrfToken) {
        postNode(csrfToken, newNode, form1)
      });
  }
  else{
    jQuery(this).closest("form").submit();
  }
});
jQuery(document).on('change', '#edit-field-is-published', function (e) {
  
    var published = jQuery('#edit-field-is-published').val();
    var d = new Date();
    var currentDate = d.getFullYear() + "-" + ((d.getMonth()+1) < 10 ? '0' : '') + (d.getMonth()+1) + "-" + (d.getDate() < 10 ? '0' : '') + d.getDate();
    var currentTime = (d.getHours() < 10 ? '0' : '') + d.getHours() + ":" + (d.getMinutes() < 10 ? '0' : '') + d.getMinutes() + ":" + (d.getSeconds() < 10 ? '0' : '') + d.getSeconds();
    if(published == '1') {
        jQuery('#edit-field-published-at-0-value-date').val(currentDate);
        jQuery('#edit-field-published-at-0-value-time').val(currentTime);
    } else {
        jQuery('#edit-field-published-at-0-value-date').val('');
        jQuery('#edit-field-published-at-0-value-time').val('');
    }

});