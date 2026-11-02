<?php

// computed fields (visibility, coordinates, date, name) before the list
// and the record are read; a no-op until ctl_temperatures or ctl_caves changes
if (function_exists("openkis_UpdateTemperatures"))
{
    openkis_UpdateTemperatures();
}
