$(".dropdown-item a").click(
        function () {
            if ($(this).next().html())
            {
                $(this).next().toggle();
                return false;
            }
            return true;
        });
