<x-outer>
    <p>{{$_SERVER["SERVER_NAME"]. ":" . $_SERVER["SERVER_PORT"] . "/" . $result["short_url"] }}</p>
    @if(config("app.debug") && $result["status"] == 0)
        <p> url already exists!! </p>
    @endif
    @if(config("app.debug") && $result["status"] & 1)
        <p> another tx just inserted the same url! </p>
    @endif
    @if(config("app.debug") && $result["status"] & 2)
        <p> collision occurred </p>
    @endif
</x-outer>