<?php

// computed fields (visibility, coordinates, regime, name) before the list
// and the record are read; a no-op until ctl_tracings or ctl_caves changes
if (function_exists("openkis_UpdateTracings"))
{
    openkis_UpdateTracings();
}
