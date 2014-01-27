{##
 # test_entry_transcribe.twig
 # 
 # Extends the base_record template for transcribing recordings.
 # @author Dean Inglis <inglisd@mcmaster.ca>
 #}
{% extends "base_record.twig" %}

{% block javascript %}

  {{ parent() }}
  <script type="text/javascript">   

    $( function () {

      if( "{{ test_type }}" == "confirmation" ) {
        $("#{{ widget.full }}__audio_control").attr( "disabled", "disabled" );
        $("#{{ widget.full }}__audio_fault").attr( "disabled", "disabled" );
      } else {
        $("#{{ widget.full }}__audio_control").removeAttr( "disabled" );
        $("#{{ widget.full }}__audio_fault").removeAttr( "disabled" );
      }

      $("#{{ widget.full }}__audio_fault").click( function() {
        var args = new Object();
        args.columns = { audio_fault : $(this).is(':checked') ? 1 : 0 };
        args.id = {{ id }};
        ajax_push( "{{ widget.subject }}", "edit", args );
      } ); // end Audio Fault click
      
      $("#{{ widget.full }}_Quit").click( function() {
        slot_load( {{ slot }}, "assignment", "list" );
      } ); // end Quit click

      $("#{{ widget.full }}_Submit").click( function() {

      } ); // end Submit click

      $( "#{{ widget.full }}_Prev").click( function() {
        slot_load( {{ slot }}, 
          "{{ widget.subject }}", "{{ widget.name }}", 
          { id : {{ prev_test_entry_id}} } );
      } ).button( { disabled : {{ rank == 1 ? 'true' : 'false' }} } ); // end Prev click
      $( "#{{ widget.full }}_Next" ).click( function() {
        {% if 0 == next_test_entry_id %}
          slot_load( {{ slot }}, "assignment", "list" );
        {% else %}  
          slot_load( {{ slot }}, 
            "{{ widget.subject }}", "{{ widget.name }}", 
            { id : {{ next_test_entry_id}} } );
        {% endif %}  
      } ); // end Next click

    } );
  </script>

{% endblock javascript %}

{% block record %}

  <div>
    
    <div class="spacer">

    <div>
      {% include [ 'test_entry_', test_type, '_adjudicate.twig']|join with test_entry_args %}      
    </div>  

    <div class="spacer">

      <div>
        <table>
          <tr>
            <td align="left"
                style="white-space: nowrap">
              <audio id="{{ widget.full }}__audio_control"
                controls="controls">
                <embed height="50" style="width:100%">
              </audio>
              <input type="checkbox" id="{{ widget.full }}__audio_fault" 
                {{ audio_fault ? "checked":"" }}/>
              <label for="{{ widget.full }}__audio_fault">Audio Fault</label>  
            </td>
            <td>
              <div>
                {% from 'macros.twig' import confirm_buttons %}
                  {{ confirm_buttons( slot, widget.full, 
                    ['Submit', 'Prev', 'Next', 'Quit'], '', 'right', true ) }}
              </div>
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <div id="{{ widget.full }}__note_dialog"></div>

{% endblock record %}
