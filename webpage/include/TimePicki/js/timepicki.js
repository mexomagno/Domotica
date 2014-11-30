/* 
 * Author: senthil
 * plugin: timepicker
 */
(function ( $ ) {

    $.fn.timepicki = function(options) {
        
        var defaults = {
            
        };
        
        var settings = $.extend( {}, defaults, options );
        
        return this.each( function() {
            
            var ele = $(this);
            var ele_hei = ele.outerHeight();
            var ele_lef = 0;//ele.position().left;
            ele_hei +=10;
            $(ele).wrap("<div class='time_pick'>");
            var ele_par = $(this).parents(".time_pick");
            ele_par.append("<div class='timepicker_wrap'><div class='arrow_top'></div><div class='time'><div class='prev'></div><div class='ti_tx'></div><div class='next'></div></div><div class='mins'><div class='prev'></div><div class='mi_tx'></div><div class='next'></div></div><div class='meridian'><div class='prev'></div><div class='mer_tx'></div><div class='next'></div></div></div>");
            var ele_next = $(this).next(".timepicker_wrap");
            var ele_next_all_child = ele_next.find("div");
            ele_next.css({ "top": ele_hei+"px", "left": ele_lef+"px", "width": "92px","padding_left":"0px !important"});
            $(document).on( "click",function(event) {
                if(!$(event.target).is(ele_next))
                    {
                        if(!$(event.target).is(ele))
                            {
                                var tim = ele_next.find(".ti_tx").html();
                                var mini = ele_next.find(".mi_tx").text();
                                var meri = ele_next.find(".mer_tx").text();
                                //agregado por mi!!!
                                //alert(""+ele.attr('class'));
                                var clases = ele.attr('class');
                                var hnum = clases.indexOf("tf_") < 0 ? -1 : clases.substring(clases.indexOf("tf_")+3);
                                //alert(""+hnum);
                                if(tim.length !=0 && mini.length !=0 && meri.length !=0 )
                                {
                                    if (document.getElementById("chb_h"+hnum).checked)
                                        ele.val(tim+":"+mini+" "+meri);
                                    else
                                        ele.val("--:--");
                                }
                                if(!$(event.target).is(ele_next)&&!$(event.target).is(ele_next_all_child))
                                {
                                   ele_next.fadeOut(); 
                                }
                            }
                            else{
                                set_date();
                                ele_next.fadeIn();  
                            }
                    }
            });
            function set_date()
            {
                var d = new Date();
                var ti = d.getHours();
                var mi = d.getMinutes();
                var mer = "am";
                if (12 < ti) {
                    ti -= 12;
                    mer = "pm";
                }
                //console.log(ele_next);
                if(ti<10)
                    {
                        ele_next.find(".ti_tx").text("0"+ti);
                    }
                    else{
                        ele_next.find(".ti_tx").text(ti);
                    }
                if(mi<10)
                    {
                        ele_next.find(".mi_tx").text("0"+mi);
                    }
                    else{
                        ele_next.find(".mi_tx").text(mi);
                    }
                if(mer<10)
                    {
                        ele_next.find(".mer_tx").text("0"+mer);
                    }
                else{
                        ele_next.find(".mer_tx").text(mer);
                    }
            }
                
                
                var cur_next = ele_next.find(".next");
                var cur_prev = ele_next.find(".prev");
                
                
            $(cur_prev).add(cur_next).on("click", function () {
                //console.log("click");                
                var cur_ele = $(this);
                var cur_cli = null;
                var ele_st = 0;
                var ele_en = 0;
                //seteo de horas
                if ((cur_ele.parent().attr("class") == "time")) {
                    cur_cli = "time";
                    ele_en = 12;
                    var cur_time = null;
                    cur_time = ele_next.find("." + cur_cli + " .ti_tx").text();
                    cur_time = parseInt(cur_time);
                    //console.log(ele_next.find("." + cur_cli + " .ti_tx"));
                    //si apreta next, aumenta hora
                    if (cur_ele.attr("class") == "next") {
                        //alert("nex");
                        if (cur_time == 12) {
                            ele_next.find("." + cur_cli + " .ti_tx").text("01");
                        } 
                        else {
                            cur_time++;

                            if(cur_time<10)
                            {
                            ele_next.find("." + cur_cli + " .ti_tx").text("0"+cur_time);
                            }
                            else{
                            ele_next.find("." + cur_cli + " .ti_tx").text(cur_time);
                            }
                        }

                    } 
                    //si apreta prev, disminuye hora
                    else {
                        if (cur_time == 1) {
                            ele_next.find("." + cur_cli + " .ti_tx").text(12);
                        } 
                        else {
                            cur_time--;
                            if(cur_time<10)
                            {
                            ele_next.find("." + cur_cli + " .ti_tx").text("0"+cur_time);
                            }
                            else{
                            ele_next.find("." + cur_cli + " .ti_tx").text(cur_time);
                            }
                        }
                    }

                } 
                //seteo de minutos
               else if (cur_ele.parent().attr("class") == "mins") {
                    //alert("mins");
                    cur_cli = "mins";
                    ele_en = 59;
                    var cur_mins = null;
                    cur_mins = ele_next.find("." + cur_cli + " .mi_tx").text();
                    cur_mins = parseInt(cur_mins);
                    if (cur_ele.attr("class") == "next") {
                        //alert("nex");
                        if (cur_mins == 59) {
                            ele_next.find("." + cur_cli + " .mi_tx").text("00");
                        } else {
                            cur_mins++;
                                           if(cur_mins<10)
                            {
                            ele_next.find("." + cur_cli + " .mi_tx").text("0"+cur_mins);
                            }
                            else{
                            ele_next.find("." + cur_cli + " .mi_tx").text(cur_mins);
                            }
                        }
                    } 
                    else {
           
                        if (cur_mins == 0) {
                           ele_next.find("." + cur_cli + " .mi_tx").text(59);
                        }
                       else {
                           cur_mins--;

                           if(cur_mins<10)
                           {
                           ele_next.find("." + cur_cli + " .mi_tx").text("0"+cur_mins);
                           }
                           else{
                           ele_next.find("." + cur_cli + " .mi_tx").text(cur_mins);
                           }

                       }

                   }
                } 
                //seteo de meridiano AM PM
                else {
                    //alert("merdian");
                    ele_en = 1;
                    cur_cli = "meridian";
                    var cur_mer = null;
                    cur_mer = ele_next.find("."+cur_cli+" .mer_tx").text();
                    if (cur_ele.attr("class") == "next") {
                        //alert(cur_mer);
                        if(cur_mer=="am"){
                          ele_next.find("."+cur_cli+" .mer_tx").text("pm");
                        }
                        else{
                         ele_next.find("."+cur_cli+" .mer_tx").text("am");
                        }
                    } else {
                        if(cur_mer=="am"){
                          ele_next.find("."+cur_cli+" .mer_tx").text("pm");
                        }
                        else{
                         ele_next.find("."+cur_cli+" .mer_tx").text("am");
                        }
                    }
                }


            });
            
        });
    };
 
}( jQuery ));

