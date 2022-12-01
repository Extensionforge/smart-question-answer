/**
 * SmartQa admin app.
 */
'use strict';

(function($) {
  SmartQa.views.Answer = Backbone.Model.extend({
    defaults: {
      ID: '',
      content: '',
      deleteNonce: '',
      comments: '',
      activity : '',
      author: '',
      editLink: '',
      trashLink: '',
      status: '',
      selected: '',
      avatar: ''
    }
  });

  SmartQa.collections.Answers = Backbone.Collection.extend({
    url: ajaxurl+'?action=asqa_ajax&asqa_ajax_action=get_all_answers&question_id='+currentQuestionID,
    model: SmartQa.views.Answer
  });

  SmartQa.views.Answer = Backbone.View.extend({
    className: 'asqa-ansm clearfix',
    id: function(){
      return this.model.get('ID');
    },
    initialize: function(options){
      if(options.model)
        this.model = options.model;
    },
    template: function(){
      return $('#asqa-answer-template').html()
    },
    render: function(){
      if(this.model){
        var t = _.template(this.template());
        this.$el.html(t(this.model.toJSON()));
      }
      return this;
    }
  });

  SmartQa.views.Answers = Backbone.View.extend({
    initialize: function(options){
      this.model = options.model;
      this.model.on('add', this.answerFetched, this);
    },
    renderItem: function(ans){
      var view = new SmartQa.views.Answer({model: ans});
      this.$el.append(view.render().$el);
    },
    render: function(){
      var self = this;
      if(this.model){
        this.model.each(function(ans){
          self.renderItem(ans);
        });
      }

      return this;
    },
    answerFetched: function(answer){
      this.renderItem(answer);
    }
  });

  if( currentQuestionID ) {
    var answers = new SmartQa.collections.Answers();
    var answersView = new SmartQa.views.Answers({model: answers, el: '#answers-list'});
    answersView.render();
    answers.fetch();
  }

})(jQuery);