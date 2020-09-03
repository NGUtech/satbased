function (commit) {
    if (/^satbased\.accounting\.payment\-/.test(commit._id) && commit.sequence) {
        emit(commit.committedAt, 1);
    }
}