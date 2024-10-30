 ////////////////////////////////////////////////////////////////////////////////////////////
 //moderateIt.js version 1.0.0
 //
 //
 jQuery(document).ready(function($) {
//////////////////////////////////////////////////////////////////////////////////////////////  
 /////// TEMPORARY VARIABLES
////////////////////////////////////////////////////////////////////////////////////////////      
if (localStorage.getItem('_mIt_User_Token')===null)    localStorage.setItem('_mIt_User_Token', '0');
if (localStorage.getItem('_mIt_Check_Success')===null) localStorage.setItem('_mIt_Check_Success', 'false');
if (localStorage.getItem('_mIt_Rules')===null)         localStorage.setItem('_mIt_Rules', '[]');
if (localStorage.getItem('_mIt_Free_Rules')===null)    localStorage.setItem('_mIt_Free_Rules', '[]');
if (localStorage.getItem('_mIt_Pre_Rule')===null)      localStorage.setItem('_mIt_Pre_Rule', '');
if (localStorage.getItem('_mIt_Min_Check_Time')===null) localStorage.setItem('_mIt_Min_Check_Time', '0');
if (localStorage.getItem('_mIt_Parts_Cnt')===null)      localStorage.setItem('_mIt_Parts_Cnt', '0');
if (localStorage.getItem('_mIt_Msg')===null)            localStorage.setItem('_mIt_Msg', '{}');

var _mIt_Rules=$.parseJSON(localStorage.getItem('_mIt_Rules')); 
var _mIt_Free_Rules=$.parseJSON(localStorage.getItem('_mIt_Free_Rules')); 
var _mIt_Pre_Rule=localStorage.getItem('_mIt_Pre_Rule');
var _mIt_Min_Check_Time=localStorage.getItem('_mIt_Min_Check_Time');  
var _mIt_Parts_Cnt=localStorage.getItem('_mIt_Parts_Cnt') ;
var _mIt_Msg=$.parseJSON(localStorage.getItem('_mIt_Msg')); 

var _mIt_Token_Start_Time=0;  
var _mIt_Token_Life_Time=0;  
var _mIt_Cur_Mod_Type='pre';  
var _mIt_Comment_Id=0;
var _mIt_Cur_Check=1;
var _mIt_Timer_Id=0;
var _mIt_Opacity_Half=0.4;
var _mIt_Opacity_None=1;
   
window.time =function(){ 
    return parseInt(new Date().getTime()/1000);
}
////////////////////////////////////////////////////////////////////////////////////////////  
///////SECTION OF FUNCTIONS
////////////////////////////////////////////////////////////////////////////////////////////      
if(!$('#mIt_modal').length) 
{ 
    var c=$('<div>');
    c.load(_mIt_WebServiceUrl+"get_control.php",
    function(){
        $('body').after(c.html());
        mIt_Init_Control();  
    });
} 
  ////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_Init_Control =function()
{ 
   if (_mIt_Lang==='en')_mIt_Msg=_mIt_En;
   if (_mIt_Lang==='ru')_mIt_Msg=_mIt_Ru;
   localStorage.setItem('_mIt_Msg', JSON.stringify(_mIt_Msg));
   for (let i = 1; i<Object.keys(_mIt_Msg).length-1; i++) 
   {
     var inx='m'+i;
     $('#mIt_m'+i).html(_mIt_Msg[inx]);
   }
   $('.mit-comment-hint').before( $(_mIt_Msg.m25));
    
};
///////////////////////////////////////////////////////////////////////////////////////////////////
$('*[data-mit_comment_id]').each(function()
{
    $(this).html(_mIt_Button_Caption);
    $(this).attr('data-toggle', "modal");
    $(this).attr('data-target', "#mIt_modal");
});  
///////////////////////////////////////////////////////////////////////////////////////////////////
$('.mit-comment-input').each(function()
{   
    $(this).prop('readonly', true);

;}); 
///////////////////////////////////////////////////////////////////////////////////////////////////
$('*[data-mit_comment_id]').click(function()
{
    _mIt_Cur_Mod_Type='post'; 
    _mIt_Comment_Id=this.dataset.mit_comment_id; 
    if (localStorage.getItem('_mIt_Check_Success')==='false')
    {
       $('.mIt_Dlgs>div').addClass('collapse'); $('.mIt_Intro').removeClass('collapse');
       mIt_Get_Token();
       $('#mIt_m10').html(_mIt_Msg.m10);
       $('#mIt_Header').html(_mIt_Msg.m1);
   }  
   else   mIt_Set_Check_Draw();
      
   if (_mIt_Rules.length>0) 
   {
        mIt_Draw_Rules($('#mIt_Rules_Intro'),_mIt_Msg.m23);
        mIt_Draw_Rules($('#mIt_Rules'),null);
   }
   
});



///////////////////////////////////////////////////////////////////////////////////////////////////
$('.mit-comment-input').click(function()
{
    _mIt_Cur_Mod_Type='pre';  
    if (localStorage.getItem('_mIt_Check_Success')==='false')
    {
           _mIt_Comment_Id=0; 
           $('.mIt_Dlgs>div').addClass('collapse'); $('.mIt_Intro').removeClass('collapse');
           $(this).attr('data-toggle', "modal");
           $(this).attr('data-target', "#mIt_modal");
           $(this).prop('readonly', true);
           mIt_Get_Token();
           $('#mIt_m10').html(_mIt_Msg.m21);
           $('#mIt_Header').html(_mIt_Msg.m1_1);

   }
   else   mIt_Set_Check_Draw();

   if (_mIt_Rules.length>0) 
   {
        mIt_Draw_Rules($('#mIt_Rules_Intro'),_mIt_Msg.m23);
        mIt_Draw_Rules($('#mIt_Rules'),null);
   }
}); 
///////////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_Btn_Choice_Draw =function()
{
     $('#mIt_Timer').css("opacity",_mIt_Opacity_Half);   
     $('#mIt_m17').prop('disabled', true);
     $('#mIt_m18').prop('disabled', true);
     $('#mIt_m17').css("opacity",_mIt_Opacity_Half);   
     $('#mIt_m18').css("opacity",_mIt_Opacity_Half);   

    setTimeout(function(){
        $('#mIt_m17').prop('disabled', false);
        $('#mIt_m18').prop('disabled', false);

        $('#mIt_m17').css("opacity",_mIt_Opacity_None);   
        $('#mIt_m18').css("opacity",_mIt_Opacity_None);   

     },  _mIt_Min_Check_Time*1000);
     
     $('#mIt_Timer').html(_mIt_Min_Check_Time);
     _mIt_Timer_Id=setInterval(mIt_Get_Check_Button_Timer, 1000); //1000 will  run it every 1 second

}

///////////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_Get_Check_Draw =function(comment)
{
     $('.mIt_Dlgs>div').addClass('collapse'); $('.mIt_Get_Check').removeClass('collapse');        
     $('#mIt_Header').html(_mIt_Msg.m7 +_mIt_Cur_Check+_mIt_Msg.m8+ _mIt_Parts_Cnt);
     $('#mIt_Topic').html(comment.topic);
     $('#mIt_Content').html(comment.content);
     $('#mIt_Rule').html(_mIt_Rules[comment.rule]);
     
 return true;   
};
///////////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_Get_Check_Button_Timer =function()
{
  var cnt=$('#mIt_Timer').html();
  cnt=cnt-1;
  $('#mIt_Timer').html(cnt);
  if (cnt <= 0)
  {  $('#mIt_Timer').html(''); 
     clearInterval(_mIt_Timer_Id);
     return;
  }
};
///////////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_Set_Check_Draw =function()
{
   $('.mIt_Dlgs>div').addClass('collapse'); $('.mIt_Set_Check').removeClass('collapse');
   $('#mIt_Header').html(_mIt_Msg.m2);  
    
  if (_mIt_Cur_Mod_Type==='post')
  {
      $('#mIt_m15').html(_mIt_Msg.m15);  
      $('#mIt_m19').removeClass('collapse');
  }
  if (_mIt_Cur_Mod_Type==='pre')
  {
      $('#mIt_Rules').addClass('collapse');      
      $('#mIt_m15').html(_mIt_Msg.m22);  
      $('#mIt_m19').addClass('collapse');
       
      $('.mit-comment-input').removeAttr( "data-toggle" );
      $('.mit-comment-input').removeAttr( "data-target" );
      $('.mit-comment-input').prop('readonly', false);
      
      return  true;
  }
      
}; 
///////////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_Message_Draw =function(message)
{
    localStorage.setItem('_mIt_User_Token', '0');
    localStorage.setItem('_mIt_Check_Success', 'false');
    $('.mIt_Dlgs>div').addClass('collapse'); $('.mIt_Message').removeClass('collapse');
    if (message==='B')
    {
            $('#mIt_Header').html(_mIt_Msg.m3);
            mIt_Message(_mIt_Msg.m4); 
            
     }
            
    if (message!=='B'&&message!=='G')
              mIt_Message(_mIt_Msg.m5+message +_mIt_Msg.m6); 
};
///////////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_Get_Check =function(token,last_res,moderator_email)
{
 var t=$.getJSON(_mIt_WebServiceUrl+"get_check.php?",
 {"user_token":token,
 "last_res":last_res,
 "moderator_email":moderator_email
 },
 
 function (data)
 { 
    if ('comment' in data)
    {   mIt_Get_Check_Draw(data.comment);
        return true;      
    }
    if ('message' in data)
    {
        
       if (data.message==='G') 
       {
            localStorage.setItem('_mIt_Check_Success', 'true');
            mIt_Set_Check_Draw();
       }
       else        
            mIt_Message_Draw(data.message);
       
        return false;      
    }
    return true;   
 });
};
///////////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_Get_Check_Bad =function()
{
    mIt_Btn_Choice_Draw();
    mIt_Get_Check(localStorage.getItem('_mIt_User_Token'),"B","");
    _mIt_Cur_Check=_mIt_Cur_Check+1;
};
///////////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_Get_Check_Good =function()
{
    mIt_Btn_Choice_Draw();
    mIt_Get_Check(localStorage.getItem('_mIt_User_Token'),"G","");
    _mIt_Cur_Check=_mIt_Cur_Check+1;
} ;

 ///////////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_Get_Token =function()
{
            var t= $.getJSON(_mIt_WebServiceUrl+"get_token.php",
            {'lang':_mIt_Lang,
                "api_key":_mIt_Api_Key
            },
            function (data)
            {
              if ('message' in data)
                    mIt_Message_Draw(data.message);
              else
              {
                localStorage.setItem('_mIt_User_Token', data.user_token);
                mIt_Get_Rules();
                
              }
            });
}; 
///////////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_Intro_Next =function(e)
{
    _mIt_Cur_Check=1;
    mIt_Btn_Choice_Draw();
    mIt_Get_Check(localStorage.getItem('_mIt_User_Token'),"0","");
}; 
///////////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_Draw_Rules =function(control,caption)
{     
       control.empty();
       if (caption!==null)   
       {
            control.append('<option selected >'+caption+'</option>');
            for (var i in _mIt_Rules)
            {
              control.append('<option disabled value='+i+'>'+_mIt_Rules[i]+'</option>');
            }
       }
       else 
       {
            for (var i in _mIt_Rules)
            {
                  var disabled='';
                  if( _mIt_Free_Rules.indexOf(_mIt_Rules[i]) == -1)  disabled='disabled';
                  var selected='';
                  if( i==_mIt_Pre_Rule)  selected='selected';
                  control.append('<option '+ selected+' '+disabled+' value='+i+'>'+_mIt_Rules[i]+'</option>');
            }
       }
      control.removeClass('collapse');
}

///////////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_Get_Rules =function()
{
   var t= $.getJSON(_mIt_WebServiceUrl+"get_rules.php",{"user_token":localStorage.getItem('_mIt_User_Token')},
   function (data)
   { 
      if ('message' in data)
            mIt_Message_Draw(data.message);
      else 
      {
       _mIt_Parts_Cnt=data.parts_cnt; 
       localStorage.setItem('_mIt_Parts_Cnt',_mIt_Parts_Cnt);
       
       _mIt_Min_Check_Time=data.min_check_time;
       localStorage.setItem('_mIt_Min_Check_Time',_mIt_Min_Check_Time);
       
       _mIt_Rules=data.rules;
       localStorage.setItem('_mIt_Rules',JSON.stringify(data.rules));
       
       _mIt_Free_Rules=data.free_rules;
       localStorage.setItem('_mIt_Free_Rules',JSON.stringify(data.free_rules));
       
       _mIt_Pre_Rule=data.pre_rule;
       localStorage.setItem('_mIt_Pre_Rule',_mIt_Pre_Rule);
       
       mIt_Draw_Rules($('#mIt_Rules_Intro'),_mIt_Msg.m23);
       mIt_Draw_Rules($('#mIt_Rules'),null);
      }
      return true;   
   });
        
};
///////////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_Set_Check_Next =function()
{
    mIt_Get_Comment_Data(_mIt_Comment_Id,'');
}

///////////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_Set_Check =function(commentData )
{
    var c = $.parseJSON(commentData);
    if (_mIt_Cur_Mod_Type=='post') 
        c.rule=$("#mIt_Rules :selected").val();
    if (_mIt_Cur_Mod_Type=='pre') 
        c.rule=_mIt_Pre_Rule;
    jsn= JSON.stringify(c); 

    var t= $.getJSON(_mIt_WebServiceUrl+"set_check.php?",
    {
    "user_token":localStorage.getItem('_mIt_User_Token'),
    "ret_url": _mIt_Ret_Url,
    "comment": jsn
    },
    function (data)
    { 
      $('.mIt_Dlgs>div').addClass('collapse'); $('.mIt_Message').removeClass('collapse');
      var new_comment= $('[data-mit_new_comment_id]');
      new_comment.removeAttr('data-mit_new_comment_id');
     
      localStorage.setItem('_mIt_User_Token', '0');
      localStorage.setItem('_mIt_Check_Success', 'false');
      if (data.message==='G')
      {
           if (_mIt_Cur_Mod_Type=='pre') 
              mIt_Message(_mIt_Msg.m24); 
          else 
              mIt_Message(_mIt_Msg.m9); 
      }
      else                 
      {
          var error =_mIt_Msg.m5+data.message +_mIt_Msg.m6;
          mIt_Message(error); 
      }
      if (_mIt_Cur_Mod_Type=='pre') 
      {
         $('.mit-comment-input').attr('data-toggle', "modal");
         $('.mit-comment-input').attr('data-target', "#mIt_modal");
         $('.mit-comment-input').prop('readonly', true);
         $('#mIt_modal').modal('show');
      }
      return true;   
    });
    
};

///////////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_New_Comment =function()
{
   mIt_Get_Comment_Data(this.dataset.mit_new_comment_id,localStorage.getItem('_mIt_User_Token'));
}

///////////////////////////////////////////////////////////////////////////////////////////////////
$('body').on('DOMNodeInserted', '[data-mit_new_comment_id]',mIt_New_Comment);//for client-side insert
$('[data-mit_new_comment_id]').each(mIt_New_Comment); //for server-side insert
///////////////////////////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////////////////////////
window.mIt_Message =function(msg)
{
    $('#mIt_Message').html(msg); 
    if (_mIt_Log_Url!='')
    {
        var t= $.getJSON(_mIt_Log_Url,{'msg':msg},   
        function (data)
        {
        });
    }
}
});  



 