function (commit) {
    if (/^satbased\.accounting\.account\-/.test(commit._id) && commit.sequence) {
        emit(commit.committedAt, 1);
    }
}