function ResultAssistant(argFromPusher) {
    /* this is the creator function for your scene assistant object. It will be passed all the 
       additional parameters (after the scene name) that were passed to pushScene. The reference
       to the scene controller (this.controller) has not be established yet, so any initialization
       that needs the scene controller should be done in the setup function below. */
    this.currentResult = argFromPusher;
}

ResultAssistant.prototype.setup = function(){
    /* set resultset area */
    result = this.currentResult;
    /* set app headline */
    this.controller.get('resultHdr').innerHTML = _APP_Result_Name;
    this.attributes = {
            itemTemplate: 'result/listitem',
            listTemplate: 'result/listcontainer',
            swipeToDelete: true,
            renderLimit: 40,
            reorderable: false,
            autoconfirmDelete: true,
            emptyTemplate:'result/emptylist'
    };
    this.model = {
            listTitle: "Search Result",
            items: result
    };
    this.controller.setupWidget("pushList", this.attributes, this.model);
   
    this.buttonAtt1 = {
            //type : 'Activity'
    };
    this.buttonModel1 = {
            buttonLabel : 'Back',
            buttonClass : '',
            disable : false
    };
    this.controller.setupWidget("backButton", this.buttonAtt1, this.buttonModel1);
    /* add listener button*/
    this.handleButtonPressBinder = this.handleButtonPress.bind(this);
    Mojo.Event.listen(this.controller.get("backButton"),
                      Mojo.Event.tap, 
                      this.handleButtonPressBinder
    )
}
ResultAssistant.prototype.handleButtonPress = function(event){
    //this.controller.get('string').update(this.text2);
    this.controller.stageController.pushScene('search');
}

ResultAssistant.prototype.activate = function(event){
    /* put in event handlers here that should only be in effect when this scene is active. For
     example, key handlers that are observing the document */
}


ResultAssistant.prototype.deactivate = function(event) {
    /* remove any event handlers you added in activate and do any other cleanup that should happen before
       this scene is popped or another scene is pushed on top */
}

ResultAssistant.prototype.cleanup = function(event) {
    /* this function should do any cleanup needed before the scene is destroyed as 
       a result of being popped off the scene stack */
   Mojo.Event.stopListening(this.controller.get('backButton'),Mojo.Event.tap, this.handleButtonPressBinder)
}