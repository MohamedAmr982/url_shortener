<x-outer>
    <h1>URL shortener!!</h1>
    <form action="/shorten" method="post">
        {{-- @csrf --}}
        <input type="url" name="url" id="" required>
        <input type="submit" value="Shorten!">
    </form>
    @error('url')
        {{ $message }}
    @enderror
</x-outer>